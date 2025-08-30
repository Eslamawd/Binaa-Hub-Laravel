<?php

// File: app/Http/Controllers/GeminiController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class GeminiController extends Controller
{
    private $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

 // File: app/Http/Controllers/GeminiController.php

// ...

public function describeBlueprint(Request $request)
{
    $request->validate([
        'description' => 'required|string',
        'blueprint' => 'required|image|max:10000', // الآن تتحقق من الصور فقط
    ]);

    $imagePath = $request->file('blueprint')->getPathname();

    $description = $this->geminiService->generateTextWithImage(
        $request->input('description'),
        $imagePath
    );

    return response()->json(['response' => $description]);
}

public function describePdfBlueprint(Request $request)
{
    $request->validate([
        'pdf_blueprint' => 'required|mimes:pdf|max:10000', // تتحقق من PDF فقط
    ]);

    $pdfPath = $request->file('pdf_blueprint')->getPathname();

    $description = $this->geminiService->generateFromPdf($pdfPath);
    
    return response()->json(['response' => $description]);
}
    public function askQuestion(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
        ]);

        $answer = $this->geminiService->generateText($request->input('question'));
        
        return response()->json(['answer' => $answer]);
    }
}