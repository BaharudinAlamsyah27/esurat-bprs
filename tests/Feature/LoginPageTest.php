<?php

it('shows the Indonesian login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('lang="id"', false)
        ->assertDontSee('fonts.bunny.net')
        ->assertSee('Masuk ke E-Surat BPRS Sragen')
        ->assertSee('Alamat email')
        ->assertSee('Kata sandi');
});
