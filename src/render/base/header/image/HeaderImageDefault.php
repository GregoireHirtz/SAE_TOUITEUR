<?php

namespace touiteur\render\base\header\image;

class HeaderImageDefault extends HeaderImage{

	public function __construct(String $p=""){
		$this->prefixe = $p;
	}

	function render(): string{
		return "<a href='#' class='photo_profil'><img src='{$this->prefixe}src/vue/images/user.svg' alt='PP'></a>";
	}
}