<?php

namespace App\Http\Controllers;

use App\Models\Predaja;
use Illuminate\Http\Request;

class StatistikaController extends Controller
{
    public function mesecno(Request $request)
    {
        $user = $request->user();

        $query = Predaja::query();

        if ($user->uloga === 'STUDENT') {
            $query->where('student_id', $user->id);
        } elseif ($user->uloga === 'PROFESOR') {
            $query->whereHas('zadatak.predmet', function ($q) use ($user) {
                $q->where('profesor_id', $user->id);
            });
        }

        $stats = $query
            ->selectRaw("DATE_FORMAT(COALESCE(submitted_at, created_at), '%Y-%m') as mesec")
            ->selectRaw('COUNT(*) as broj_predaja')
            ->selectRaw('ROUND(AVG(ocena), 2) as prosecna_ocena')
            ->groupBy('mesec')
            ->orderBy('mesec')
            ->get()
            ->map(fn($row) => [
                'mesec' => $row->mesec,
                'broj_predaja' => (int) $row->broj_predaja,
                'prosecna_ocena' => $row->prosecna_ocena !== null ? (float) $row->prosecna_ocena : null,
            ]);

        return response()->json($stats);
    }
}