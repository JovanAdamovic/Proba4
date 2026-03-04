<?php

namespace App\Http\Controllers;

use App\Models\Predaja;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StatistikaController extends Controller
{
    #[OA\Get(
        path: "/api/statistika/mesecno",
        summary: "Mesečna statistika predaja i prosečne ocene.",
        tags: ["Statistika"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Uspešno vraćena mesečna statistika",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "mesec", type: "string", example: "2026-03"),
                            new OA\Property(property: "broj_predaja", type: "integer", example: 12),
                            new OA\Property(property: "prosecna_ocena", type: "number", format: "float", nullable: true, example: 8.25),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: "Neautorizovan pristup"
            )
        ]
    )]
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