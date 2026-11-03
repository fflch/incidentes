<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Alert;

class AlertCrudTest extends DuskTestCase
{
    use DatabaseMigrations;
    public function test_crud_alerts(): void
    {
        $this->browse(function (Browser $browser) {
            // Login
            $browser->visit('/')
                ->clickLink('Entrar')
                ->waitFor('#loginUsuario')
                ->typeSlowly('#loginUsuario', '111111')
                ->press('Login');
                
            // Create
            $browser->visit('/alerts/create')
                ->typeSlowly('id_externo', 'doloremque')
                ->press('Enviar')
                ->assertPathIs('/alerts')
                ->assertSee('doloremque');

            // Read Index (search)
            $browser->visit('/alerts')
                ->type('search', 'doloremque')
                ->press('Pesquisar')
                ->assertSee('doloremque')
                ->type('search', 'TextoInexistenteXYZ')
                ->press('Pesquisar')
                ->assertDontSee('doloremque');

            // Read Show
            $browser->visit('/alerts')
                ->clickLink('doloremque')
;

            // Update
            $browser->clickLink('Editar')
                ->typeSlowly('id_externo', 'doloremque - Editado')
                ->press('Enviar')
                ->assertSee('doloremque - Editado');

            // Delete
            $browser->press('Apagar')
                ->acceptDialog()
                ->assertPathIs('/alerts')
                ->assertDontSee('doloremque - Editado');
        });
    }
}
