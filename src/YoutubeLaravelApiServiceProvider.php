<?php

namespace alchemyguy\YoutubeLaravelApi;

use Illuminate\Support\ServiceProvider;

class YoutubeLaravelApiServiceProvider extends ServiceProvider {
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot() {
		$this->publishes(array(__DIR__ . '/config/google-config.php' => config_path('google-config.php')), 'youtube-config');
	}

	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register() {
		//
	}
}