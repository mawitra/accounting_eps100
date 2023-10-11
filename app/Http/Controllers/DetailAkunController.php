<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DetailAkunController extends Controller
{
    public function index()
    {

        $detail = DB::select("SELECT account_code, account_name, account_type, created_at, updated_at FROM detail_akun");

        $responseData = [
            'status_code' => 200,
            'message' => "succes",
            'data' => $detail
        ];

        return response()->json($responseData);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'account_code' => 'required',
            'account_name' => 'required',
            'account_type' => 'required',
        ]);

        try {
            DB::insert('INSERT INTO detail_akun (account_code, account_name, account_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?)', [
                $request->input('account_code'),
                $request->input('account_name'),
                $request->input('account_type'),
                Carbon::now(),
                Carbon::now(),
            ]);

            $responseData = [
                'status_code' => 201, // 201 Created status code
                'message' => 'Data Detail Akun berhasil dibuat.',
                'data' => [
                    'no akun' => $request->input('account_code'),
                    'nama akun' => $request->input('account_name'),
                    'tipe akun' => $request->input('account_type'),
                ],
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500, // 500 Internal Server Error status code
                'message' => 'Gagal membuat data Detail Akun.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $accountCode)
    {
        $this->validate($request, [
            'account_name' => 'required',
            'account_type' => 'required',
        ]);

        try {
            $affectedRows = DB::update('UPDATE detail_akun SET account_name = ?, account_type = ?, updated_at = ? WHERE account_code = ?', [
                $request->input('account_name'),
                $request->input('account_type'),
                Carbon::now(),
                $accountCode,
            ]);

            if ($affectedRows === 1) {
                $responseData = [
                    'status_code' => 200, // 200 OK status code
                    'message' => 'Data Detail Akun berhasil diperbarui.',
                    'data' => [
                        'no akun' => $accountCode,
                        'nama akun' => $request->input('account_name'),
                        'tipe akun' => $request->input('account_type'),
                    ],
                ];

                return response()->json($responseData);
            } else {
                return response()->json([
                    'status_code' => 404, // 404 Not Found status code
                    'message' => 'Data Detail Akun tidak ditemukan.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500, // 500 Internal Server Error status code
                'message' => 'Gagal memperbarui data Detail Akun.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('detail_akun')->where('id', $id)->delete();

            $responseData = [
                'status_code' => 200, // 200 OK status code
                'message' => 'Data Detail Akun berhasil dihapus.',
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500, // 500 Internal Server Error status code
                'message' => 'Gagal menghapus data Detail Akun.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
