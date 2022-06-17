<?php

use Inertia\Inertia;

Route::get('home', function(){
  return Inertia::render('home');
});
