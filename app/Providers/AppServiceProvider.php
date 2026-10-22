<?php

namespace App\Providers;

use App\Support\FileSize;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu pembacaan data.json per request/job.
        $this->app->scoped(\App\Services\AppSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        $isHttps = request()->isSecure() || request()->header('x-forwarded-proto') === 'https';

        if (config('app.env') === 'production' || $isHttps) {
            URL::forceScheme('https');
        }

        // Batas ukuran berkas di pesan validasi tampil sebagai MB/KB ("10 MB"), bukan
        // kilobita mentah ("10240 kilobita"). Selain berkas, angka dipasang apa adanya.
        foreach (['max' => [':max'], 'min' => [':min'], 'size' => [':size'], 'between' => [':min', ':max']] as $rule => $placeholders) {
            Validator::replacer($rule, function ($message, $attribute, $rule, $parameters, $validator) use ($placeholders) {
                if ($validator->getValue($attribute) instanceof UploadedFile) {
                    $parameters = array_map(FileSize::fromKilobytes(...), $parameters);
                }

                return str_replace($placeholders, $parameters, $message);
            });
        }
    }
}
