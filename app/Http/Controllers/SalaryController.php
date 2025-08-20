<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SalaryNotification;
use App\Imports\SalaryImport;
use Maatwebsite\Excel\Facades\Excel;

class SalaryController extends BaseController
{
    public function __construct()
    {
        $this->model = new \App\Models\Salary();
        $this->relations = [];
        $this->filterableFields = ['status'];
    }

    public function store(Request $request)
    {
        $file = $request->file('file_path');
        $fileName = null;

        if ($file) {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/files', $fileName);
        }

        $newRequestData = $request->except(['file_path']) + [
            'file_path' => $fileName ? 'public/files/' . $fileName : null,
        ];

        return parent::store(new Request($newRequestData));
    }

    public function update(Request $request, $id)
    {
        $salary = $this->model::findOrFail($id);

        $data = $request->except('file_path');

        try {
            if ($request->hasFile('file_path')) {

                if ($salary->file_path && file_exists(storage_path('app/' . $salary->file_path))) {
                    Storage::delete($salary->file_path);
                }

                $file = $request->file('file_path');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/files', $fileName);

                $data['file_path'] = 'public/files/' . $fileName;
            }

            $salary->update($data);

            return self::crudSuccess($salary, 'updated');
        } catch (\Exception $e) {
            return self::error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $salary = $this->model::findOrFail($id);

        if ($salary->file_path && file_exists(storage_path('app/' . $salary->file_path))) {
            Storage::delete($salary->file_path);
        }

        return parent::destroy($id);
    }

    public function sendEmails()
    {
        set_time_limit(4500);

        $salaries = $this->model::where('status', '=', 0)->get();

        if ($salaries->isEmpty()) {
            return self::success(['message' => 'Tidak ada data untuk dikirim.'], false);
        }

        $successCount = 0;

        foreach ($salaries as $salary) {
            if (!$salary->file_path || !Storage::exists($salary->file_path)) {
                $salary->update(['status' => 2]);
                continue;
            }

            try {
                Mail::to($salary->email)->send(new SalaryNotification($salary));

                $salary->update(['status' => 1]);

                $successCount++;

            } catch (\Exception $e) {
                $salary->update(['status' => 2]);

                // Log::error('Error sending email to ' . $salary->email . ': ' . $e->getMessage());
            }

            // Tunggu selama 5 detik sebelum mengirim email berikutnya
            sleep(5);
        }

        // Persiapkan pesan hasil proses pengiriman email
        $message = [
            'message' => "Proses pengiriman email selesai. {$successCount} email berhasil dikirim.",
        ];

        // Kembalikan respons sukses dengan pesan hasil proses
        return self::crudSuccess($message, false);
    }

     public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,csv',
        ]);

        if ($validator->fails()) {
            return self::error($validator->errors()->getMessages(), 422);
        }

        try {
            $file = $request->file('file');

            Excel::import(new SalaryImport, $file);

            return self::crudSuccess(['message' => 'Data berhasil diimpor'], false);
        } catch (\Exception $e) {
            return self::error($e->getMessage(), 500);
        }
    }
}
