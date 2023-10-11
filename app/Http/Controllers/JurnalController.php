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
        $jurnal = DB::select("
            SELECT j.jurnal_id, j.comp_id, j.account_code, j.financial_type, j.amount, j.transaction_date, d.account_code, d.account_name, d.account_type
            FROM jurnal j
            LEFT JOIN detail_akun d ON j.account_code = d.account_code
        ");

        $responseData = [
            'status_code' => 200,
            'message' => 'Success',
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

        // Fetch input from the request
        $compId = $request->input('comp_id');
        $accountCode = $request->input('account_code');
        $financialType = $request->input('financial_type');
        $amount = $request->input('amount');
        $transactionDate = $request->input('transaction_date');

        // Verify if the account code exists in 'detail_akun'
        $accountCodeInfo = DB::selectOne("SELECT account_code FROM detail_akun WHERE account_code = ?", [$accountCode]);

        if (!$accountCodeInfo) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Data detail akun tidak ditemukan.',
            ]);
        }

        $accountCode = $accountCodeInfo->account_code;
        //     
        DB::beginTransaction();

        try {

            $jurnalId = Uuid::uuid4()->toString();
            $time = Carbon::now();
            $balance = $amount;
            // Check if there's an existing record with the same comp_id in the kalkulasi table
            $existingKalkulasi = DB::table('kalkulasi')
                ->where('comp_id', $compId)
                ->first();
            $matchingDebit = DB::selectOne("
            SELECT jurnal_id
            FROM jurnal
            WHERE comp_id = ? AND financial_type = 'debit' AND amount = ?", [$compId, $amount]);

            if ($financialType === 'kredit' && !$matchingDebit) {
                return response()->json([
                    'status_code' => 400,
                    'message' => 'No matching "debit" entry with the same comp_id, account_code, and amount.',
                ]);
            }
            if ($existingKalkulasi) {
                // If an existing record is found, update the 'hitung' field
                $newBalance = $existingKalkulasi->hitung + ($financialType === 'debit' ? $amount : -$amount);

                DB::table('kalkulasi')
                    ->where('id', $existingKalkulasi->id)
                    ->update([
                        'hitung' => $newBalance,
                        'transaction_date' => $transactionDate,
                        'updated_at' => Carbon::now(),
                    ]);
            } else {
                // Insert a new entry into the 'kalkulasi' table
                DB::statement("
                        INSERT INTO kalkulasi (jurnal_id, comp_id, account_code, financial_type, amount, transaction_date, hitung, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ", [$jurnalId, $compId, $accountCode, $financialType, $amount, $transactionDate, $balance, $time, $time]);
            }
            // Insert a new entry into the 'kalkulasi' table


            DB::statement("
                    INSERT INTO jurnal (jurnal_id, comp_id, account_code, financial_type, amount, transaction_date, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [$jurnalId, $compId, $accountCode, $financialType, $amount, $transactionDate, $time, $time]);

            DB::commit();

            return response()->json([
                'status_code' => 201,
                'message' => 'Data Berhasil ditambahkan atau diperbarui!!',
                // Include the updated or created data here
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status_code' => 500,
                'message' => 'Gagal menambahkan atau memperbarui data',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $jurnalId)
    {
        $this->validate($request, [
            'financial_type' => 'required',
            'amount' => 'required',
            'transaction_date' => 'required',
        ]);

        // Fetch input from the request
        $financialType = $request->input('financial_type');
        $amount = $request->input('amount');
        $transactionDate = $request->input('transaction_date');

        DB::beginTransaction();

        try {
            // Update the jurnal and kalkulasi tables using a single raw SQL query
            DB::statement("
            UPDATE jurnal
            SET financial_type = ?, amount = ?, transaction_date = ?, updated_at = ?
            WHERE jurnal_id = ?;
            
            UPDATE kalkulasi
            SET financial_type = ?, amount = ?, transaction_date = ?, updated_at = ?
            WHERE jurnal_id = ?;
        ", [
                $financialType, $amount, $transactionDate, Carbon::now(), $jurnalId,
                $financialType, $amount, $transactionDate, Carbon::now(), $jurnalId
            ]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => 'Data Berhasil diperbarui!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status_code' => 500,
                'message' => 'Gagal memperbarui data',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
