<?php
namespace App\Services\Article;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageCompressionService {
    public function compressAndStore(UploadedFile $file, string $path = 'articles'): string {
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $filepath = $file->storeAs($path, $filename, 'public');
        // TODO: Implémenter compression réelle avec Intervention Image ou GD
        return $filepath;
    }

    public function delete(string $url): void {
        Storage::disk('public')->delete($url);
    }
}
