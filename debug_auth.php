<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$output = "";
$output .= "Default Guard: " . config('auth.defaults.guard') . "\n";
$output .= "Web Guard Class: " . get_class(auth()->guard('web')) . "\n";
if (method_exists(auth()->guard('web'), 'getProvider')) {
    $output .= "Web Guard has getProvider\n";
} else {
    $output .= "Web Guard MISSING getProvider\n";
}

try {
    $filamentGuard = Filament\Facades\Filament::auth();
    $output .= "Filament Guard Class: " . get_class($filamentGuard) . "\n";
    if (method_exists($filamentGuard, 'getProvider')) {
        $output .= "Filament Guard has getProvider\n";
    } else {
        $output .= "Filament Guard MISSING getProvider\n";
    }
} catch (\Exception $e) {
    $output .= "Error getting Filament Guard: " . $e->getMessage() . "\n";
}

file_put_contents('debug_output.txt', $output);
echo "Done\n";
