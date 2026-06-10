<?php
namespace App\Jobs;
use App\Models\ArticleImage;
use App\Services\Article\ImageCompressionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CompressArticleImage implements ShouldQueue
{
    use Queueable;

    public function __construct(public ArticleImage $image) {}

    public function handle(ImageCompressionService $compression): void
    {
        //
    }
}
