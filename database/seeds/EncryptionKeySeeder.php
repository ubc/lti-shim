<?php

use Illuminate\Database\Seeder;

use App\Models\EncryptionKey;

class EncryptionKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(EncryptionKey::class)->create();
    }
}
