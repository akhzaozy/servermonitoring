<?php

namespace App\Providers;

use App\Models\Server;
use App\Support\Icons;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Blade::directive('icon', fn (string $expression) => "<?php echo \\App\\Support\\Icons::svg({$expression}); ?>");

        View::composer('pages.*', function ($view) {
            Server::local();
            $servers = Server::where('is_active', true)->orderByRaw("type = 'local' desc")->orderBy('id')->get();
            $current = $servers->firstWhere('id', (int) session('server_id')) ?? $servers->first();

            $view->with([
                'servers' => $servers,
                'currentServer' => $current,
                'iconSet' => Icons::all(),
            ]);
        });
    }
}
