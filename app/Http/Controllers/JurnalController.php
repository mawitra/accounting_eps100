<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Ramsey\Uuid\Uuid;

class JurnalController extends Controller
{
    public function index()
    {
       
        $jurnal = DB::select("SELECT jurnal_id, comp_id, account_code, financial_type, amount, transaction_date FROM jurnal");
    
        $responseData = [
            'status_code'=> 200,
            'message'=> "succes",
            'data' => $jurnal
        ];
           
        return response()->json($responseData);
    }

    
    public function store(Request $request)
{
    $this->validate($request, [
        'comp_id' => 'required',
        'account_code' => 'required',
        'financial_type' => 'required',
        'amount' => 'required',
        'transaction_date' => 'required',
    ]);

    // Ambil input dari request
    $compId = $request->input('comp_id');
    $accountCode = $request->input('account_code');
    $financialType = $request->input('financial_type');
    $amount = $request->input('amount');
    $transactionDate = $request->input('transaction_date');

    // Ambil account_code dari tabel detail_akun menggunakan SQL mentah
    $accountCode = DB::selectOne("SELECT account_code FROM detail_akun WHERE account_code = ?", [$accountCode]);

    if (!$accountCode) {
        return response()->json([
            'status_code' => 404,
            'message' => 'Data detail akun tidak ditemukan.',
        ]);
    }

    $accountCode = $accountCode->account_code;

    // Cek apakah sudah ada data dengan 'comp_id' yang sama
    $existingRecord = DB::table('jurnal')->where('comp_id', $compId)->first();

    // Inisialisasi jumlah perhitungan
    $calculationAmount = $amount;

    if ($existingRecord) {
        // Jika 'comp_id' sudah ada, update perhitungan 'hitung'
        $totalCalculation = DB::table('kalkulasi')
            ->where('jurnal_id', $existingRecord->jurnal_id)
            ->sum('hitung');

        if ($existingRecord->financial_type === 'debit') {
            $calculationAmount = $amount + $totalCalculation;
        } elseif ($existingRecord->financial_type === 'kredit') {
            $calculationAmount = $amount - $totalCalculation;
        }

        DB::table('kalkulasi')
            ->where('jurnal_id', $existingRecord->jurnal_id)
            ->update([
                'hitung' => $calculationAmount,
                'updated_at' => Carbon::now(),
            ]);

        return response()->json([
            'status_code' => 200,
            'message' => 'Data berhasil diperbarui!',
            'data' => [
                'jurnal_id' => $existingRecord->jurnal_id,
                'comp_id' => $compId,
                'account_code' => $accountCode,
                'financial_type' => $financialType,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
            ],
        ]);
    } else {
        $jurnalId = Uuid::uuid4()->toString();
        $time = Carbon::now();

        DB::beginTransaction();

        try {
            // Gunakan SQL mentah untuk memasukkan data ke tabel 'jurnal'
            DB::statement("
                INSERT INTO jurnal (jurnal_id, comp_id, account_code, financial_type, amount, transaction_date, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [$jurnalId, $compId, $accountCode, $financialType, $amount, $transactionDate, $time, $time]);

            // Gunakan SQL mentah untuk memasukkan data ke tabel 'kalkulasi'
            DB::statement("
                INSERT INTO kalkulasi (jurnal_id, comp_id, account_code, financial_type, amount, transaction_date, hitung, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$jurnalId, $compId, $accountCode, $financialType, $amount, $transactionDate, $calculationAmount, $time, $time]);

            DB::commit();

            return response()->json([
                'status_code' => 201,
                'message' => 'Data Berhasil ditambahkan!!',
                'data' => [
                    'jurnal_id' => $jurnalId,
                    'comp_id' => $compId,
                    'account_code' => $accountCode,
                    'financial_type' => $financialType,
                    'amount' => $amount,
                    'transaction_date' => $transactionDate,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status_code' => 500,
                'message' => 'Gagal menambahkan data',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
// public function store(Request $request)
//     {
//       $this->validate($request, [
//         'comp_id' => 'required',
//         'account_code' => 'required',
//         'financial_type' => 'required',
//         'amount' => 'required',
//         'transaction_date' => 'required',
//     ]);

//     // Ambil input dari request
//     $compId = $request->input('comp_id');
//     $accountCode = $request->input('account_code');
//     $financialType = $request->input('financial_type');
//     $amount = $request->input('amount');
//     $transactionDate = $request->input('transaction_date');

//     // Ambil account_code dari tabel detail_akun menggunakan SQL mentah
//     $accountCode = DB::selectOne("SELECT account_code FROM detail_akun WHERE account_code = ?", [$accountCode]);

//     if (!$accountCode) {
//         return response()->json([
//             'status_code' => 404,
//             'message' => 'Data detail akun tidak ditemukan.',
//         ]);
//     }

//     $accountCode = $accountCode->account_code;

//     // Cek apakah sudah ada data dengan 'comp_id' yang sama
//     $existingRecord = DB::table('jurnal')->where('comp_id', $compId)->first();

//     // Inisialisasi jumlah perhitungan
//     $calculationAmount = $amount;
//     // Hitung total "hitung" dari tabel "kalkulasi" berdasarkan "account_code" dan "financial_type" yang sesuai.
//     $totalCalculation = DB::table('kalkulasi')
//         ->where('account_code', $accountCode)
//         ->where('financial_type', $financialType)
//         ->sum('hitung');

//     // Hitung jumlah total "amount" dari tabel "jurnal" yang sesuai dengan "account_code" dan "financial_type".
//     $totalAmount = DB::table('jurnal')
//         ->where('account_code', $accountCode)
//         ->where('financial_type', $financialType)
//         ->sum('amount');

//     // Sesuaikan perhitungan "amount" berdasarkan tipe akun (debit atau kredit).
//     if ($financialType === 'debit') {
//         $calculationAmount = $amount + $totalCalculation;
//     } elseif ($financialType === 'kredit') {
//         $calculationAmount = $amount - $totalCalculation;
//     } else {
//         $calculationAmount = $amount; // Logika lainnya (jika ada)
//     }

//     // Check if a record with the same 'comp_id' already exists with a different 'amount'
//     $existingRecord = DB::table('jurnal')
//         ->where('comp_id', $compId)
//         ->where('amount', '<>', $amount)
//         ->first();

//     if ($existingRecord) {
//         // If a record with the same 'comp_id' and different 'amount' exists, return a validation error
//         return response()->json([
//             'status_code' => 400, // 400 Bad Request status code
//             'message' => 'ammount harus sama !!',
//         ]);
//     }

//     // Generate UUID untuk jurnal_id
//     $jurnalId = Uuid::uuid4()->toString();

//     $time = Carbon::now();

//     // Mulai transaksi database
//     DB::beginTransaction();

//     try {
//         // Gunakan pernyataan SQL mentah untuk memasukkan data ke tabel 'jurnal'
//         DB::statement("
//             INSERT INTO jurnal (jurnal_id, comp_id, account_code, financial_type, amount, transaction_date, created_at, updated_at)
//             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
//         ", [$jurnalId, $compId, $accountCode, $financialType, $amount, $transactionDate, $time, $time]);

//         // Gunakan pernyataan SQL mentah untuk memasukkan data ke tabel 'kalkulasi'
//         DB::statement("
//             INSERT INTO kalkulasi (jurnal_id, comp_id, account_code, financial_type, amount, transaction_date, hitung, created_at, updated_at)
//             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
//         ", [$jurnalId, $compId, $accountCode, $financialType, $amount, $transactionDate, $calculationAmount, $time, $time]);

        
//         // Commit transaksi jika berhasil
//         DB::commit();

//         return response()->json([
//             'status_code' => 201,
//             'message' => 'Data Berhasil di tambahkan!!',
//             'data' => [
//                 'jurnal_id' => $jurnalId,
//                 'comp_id' => $compId,
//                 'account_code' => $accountCode,
//                 'financial_type' => $financialType,
//                 'amount' => $amount,
//                 'transaction_date' => $transactionDate,
//             ],
//         ]);
//     } catch (\Exception $e) {
//         // Rollback transaksi jika terjadi kesalahan
//         DB::rollBack();

//         return response()->json([
//             'status_code' => 500, // 500 Internal Server Error status code
//             'message' => 'Failed to create data',
//             'error' => $e->getMessage(),
//         ]);
//     }
// }
}
