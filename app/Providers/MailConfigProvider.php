<?php

namespace App\Providers;

use App\Models\EmailConfiguration;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class MailConfigProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {

        // get email view data in provider class
        if (Schema::hasTable('email_configurations')) {
            $mail = DB::table('email_configurations')->first();
            if ($mail) //checking if table is not empty
            {
                $config = array(
                    'driver' => $mail->driver,
                    'host' => $mail->host,
                    'port' => $mail->port,
                    'from' => array('address' => $mail->user_name, 'name' => 'monitoring'),
                    'encryption' => $mail->encryption,
                    'username' => $mail->user_name,
                    'password' => $mail->password,
                    "sender_name" => $mail->user_name,
                    'sendmail' => '/usr/sbin/sendmail -bs',
                    'pretend' => false,
                );
                Config::set('mail', $config);

            }
        }
    }
}
