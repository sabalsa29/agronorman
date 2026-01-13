<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::connection('norman_prod')->table('users')->orderBy('id')->chunk(1000, function ($registros) {
            foreach ($registros as $registro) {

                $existe = User::where('id', $registro->id)->exists();

                if (!$existe) {
                    $nuevo = new User();
                    $nuevo->id                  = $registro->id;
                    $nuevo->nombre              = $registro->name;
                    $nuevo->email               = $registro->email;
                    $nuevo->password            = $registro->password;
                    $nuevo->cliente_id          = $registro->cliente_id;
                    $nuevo->status              = $registro->estatus;
                    $nuevo->save();
                }
            }
        });
        $this->info('Sincronización completada.');
    }
}
