<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use App\Models\User;

ini_set('max_execution_time', '60');
echo "start\n"; flush();

$email = 'test-reset@example.com';
$u = User::where('email', $email)->first();
if (!$u) {
    $u = User::create([
        'name' => 'Test Reset',
        'email' => $email,
        'phone' => '1112223334',
        'password' => Hash::make('oldpassword123'),
        'role' => 'acheteur',
        'status' => 'actif',
    ]);
    echo "created user\n"; flush();
}

config(['mail.default' => 'array']);
echo "mailer set to array\n"; flush();

echo "sending reset link (array mailer)...\n"; flush();
$status = Password::sendResetLink(['email' => $email]);
echo 'SEND_STATUS=' . $status . "\n"; flush();

$row = DB::table('password_reset_tokens')->where('email', $email)->first();
echo 'TOKEN_STORED=' . ($row ? 'yes' : 'no') . "\n"; flush();

$sent = app('mailer')->getSymfonyTransport()->getMessages();
echo 'ARRAY_MAIL_COUNT=' . count($sent) . "\n"; flush();
if (count($sent) > 0) {
    $body = $sent[0]->getTextBody();
    echo 'BODY_CONTAINS_LINK=' . (str_contains($body, '/password/reset/') ? 'yes' : 'no') . "\n"; flush();
    $link = null;
    if (preg_match('#(https?://[^\s]+/password/reset/[^\s]+)#', $body, $m)) {
        $link = rtrim($m[1], ")\n ");
        echo "RESET_LINK=" . $link . "\n"; flush();
    }
}

DB::table('password_reset_tokens')->where('email', $email)->delete();
echo "DONE\n";
