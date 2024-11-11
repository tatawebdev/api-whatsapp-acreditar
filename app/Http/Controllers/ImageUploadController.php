<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        // Validação do arquivo
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        // Verificar se há um arquivo no request
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            // Salvar a imagem no diretório 'public/images'
            $imagePath = $request->file('file')->store('images', 'public');

            // Retornar a URL da imagem para ser usada no frontend
            return response()->json([
                'imageUrl' => Storage::url($imagePath),
            ]);
        }

        // Se falhar, retornar um erro
        return response()->json([
            'error' => 'Falha no upload da imagem.',
        ], 400);
    }
}
