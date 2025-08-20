<?php

namespace App\Http\Controllers;

use App\Models\TelegramRecipient;
use App\Traits\SetReponses;
use Irazasyed\Telegram\Facades\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram as FacadesTelegram;

class TelegramController extends BaseController
{
    use SetReponses;
    protected $botToken;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
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


    public function sendBulkMessages(Request $request)
    {
        Log::info("Mulai proses pengiriman pesan ke Telegram");

        $employees = TelegramRecipient::where('status', 0)->get();

        Log::info("Jumlah karyawan ditemukan: " . $employees->count());

        if ($employees->isEmpty()) {
            Log::warning("Tidak ada data karyawan dengan status = 0");
            return self::info('Tidak ada data karyawan untuk dikirim', 404);
        }

        foreach ($employees as $employee) {
            try {
                Log::info("Memulai pengiriman ke chat_id: " . $employee->chat_id);

                $fullPath = storage_path('app/' . $employee->file_path);
                Log::info("Mencari file di path: {$fullPath}");

                $multipart = [
                    ['name' => 'chat_id', 'contents' => $employee->chat_id],
                    ['name' => 'caption', 'contents' => 'Ini adalah dokumen gaji Anda.'],
                ];

                if ($employee->file_path && file_exists($fullPath)) {
                    Log::info("File ditemukan. Persiapan mengirim dokumen.");
                    $multipart[] = [
                        'name'     => 'document',
                        'contents' => fopen($fullPath, 'rb'),
                        'filename' => basename($fullPath),
                    ];
                    $url = "https://api.telegram.org/bot{$this->botToken}/sendDocument";
                } else {
                    Log::warning("File tidak ditemukan atau kosong. Beralih ke sendMessage.");
                    $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
                    $multipart = [
                        ['name' => 'chat_id', 'contents' => $employee->chat_id],
                        ['name' => 'text', 'contents' => 'Silakan ambil dokumen gaji Anda.']
                    ];
                }

                Log::info("Menyusun payload: " . json_encode($multipart));

                $client = new \GuzzleHttp\Client();
                $response = $client->post($url, [
                    'multipart' => $multipart
                ]);

                $result = json_decode($response->getBody(), true);
                Log::info("Respons dari Telegram: " . json_encode($result));

                if (isset($result['ok']) && $result['ok']) {
                    Log::info("Pengiriman ke {$employee->chat_id} berhasil. Update status ke 1.");
                    $employee->update(['status' => 1]);
                } else {
                    Log::error("Pengiriman ke {$employee->chat_id} gagal. Update status ke 2.");
                    $employee->update(['status' => 2]);
                }
            } catch (\Exception $e) {
                Log::error("Terjadi exception saat mengirim ke {$employee->chat_id}: " . $e->getMessage());
                $employee->update(['status' => 2]);
            }
        }

        Log::info("Selesai. Semua pesan telah diproses.");

        return self::crudSuccess([], 'pengiriman selesai');
    }
}
