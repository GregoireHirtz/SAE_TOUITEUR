<?php
declare(strict_types=1);
namespace touiteur\dispatch;

use touiteur\action\accueil\GenererHeader;
use touiteur\action\accueil\GenererAccueil;
use touiteur\action\accueil\GenererFooter;

use touiteur\action\login\ActionLogin;
use touiteur\action\login\ActionSignin;

use touiteur\auth\Auth;
use touiteur\auth\Session;
use touiteur\exception\InvalideTypePage;

class Dispatcher{

		
	public function run(string $page): void{
		switch ($page){

			// afficher les touite decroissant
			case TYPE_PAGE_ACCUEIL:
				$htmlHeader = GenererHeader::execute();

				$htmlMain = GenererAccueil::execute();

				$htmlFooter = GenererFooter::execute();

				include 'src/vue/accueil.html';
				break;

			case TYPE_PAGE_PROFILE:
				global $parts;
				$username = $parts[1];
				if (!Auth::usernameExists($username)){
					header("Location: /notfound");
				}
				break;

			case TYPE_PAGE_TOUIT:
				break;

			case TYPE_PAGE_LOGIN:
				$htmlHeader = GenererHeader::execute();


				// si déjà connecté ==> accueil
				if (!empty($_SESSION)){
					// redirection vers accueil
					header("Location: /");
				}
				$htmlLoginMessage = '';
				$htmlSigninMessage = '';
				// si validation formulaire
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					// -- FORMULAIRE LOGIN -- //
					if ($_GET["action"]=="login"){
						$htmlLoginMessage = ActionLogin::execute();
					}

					// -- FORMULAIRE SIGNIN -- //
					elseif ($_GET["action"]=="signin"){
						$htmlSigninMessage = ActionSignin::execute();
					}
				}
				include 'src/vue/login.html';
				break;


			case TYPE_PAGE_UNLOGIN:
				Session::unloadSession();
				header("Location: /");
				break;

			case TYPE_PAGE_NOTFOUND:
				var_dump("404");
				break;

			default:
				throw new InvalideTypePage($page);
		}
	}
}