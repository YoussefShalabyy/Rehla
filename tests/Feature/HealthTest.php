<?php

declare(strict_types=1);

it('returns a successful health check response', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'VistaStay API is running.',
                 'data' => [
                     'version' => 'v1',
                 ],
             ]);
});
