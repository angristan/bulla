<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Import\ExportToJson;
use App\Actions\Import\ImportFromDisqus;
use App\Actions\Import\ImportFromIsso;
use App\Actions\Import\ImportFromJson;
use App\Actions\Import\ImportFromWordPress;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Import/Index');
    }

    public function importIsso(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        try {
            $result = ImportFromIsso::run(Storage::disk('local')->path($path));

            return response()->json([
                'success' => true,
                'message' => "Imported {$result['threads']} threads and {$result['comments']} comments",
                'threads' => $result['threads'],
                'comments' => $result['comments'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
            ], 422);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    public function importJson(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400', 'mimes:json'],
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        try {
            $result = ImportFromJson::run(Storage::disk('local')->path($path));

            return response()->json([
                'success' => true,
                'message' => "Imported {$result['threads']} threads and {$result['comments']} comments",
                'threads' => $result['threads'],
                'comments' => $result['comments'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
            ], 422);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    public function importWordPress(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        try {
            $result = ImportFromWordPress::run(Storage::disk('local')->path($path));

            return response()->json([
                'success' => true,
                'message' => "Imported {$result['threads']} threads and {$result['comments']} comments",
                'threads' => $result['threads'],
                'comments' => $result['comments'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
            ], 422);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    public function importDisqus(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        try {
            $result = ImportFromDisqus::run(Storage::disk('local')->path($path));

            return response()->json([
                'success' => true,
                'message' => "Imported {$result['threads']} threads and {$result['comments']} comments",
                'threads' => $result['threads'],
                'comments' => $result['comments'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
            ], 422);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    public function export(): JsonResponse
    {
        $data = ExportToJson::run();

        return response()->json($data);
    }
}
