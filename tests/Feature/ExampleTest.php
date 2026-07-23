<?php

it('redirects guests from the root to the login screen', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
