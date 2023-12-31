<?php
declare(strict_types=1);
namespace touiteur\dispatch;

use DateTime;
use PDO;
use touiteur\classe\Touite;
use touiteur\action\accueil\GenererPagination;
use touiteur\action\accueil\GenererTouit;
use touiteur\action\touit\ActionNouveauTouit;
use touiteur\action\touit\ActionSupprimerTouit;
use touiteur\action\accueil\GenererAccueil;
use touiteur\action\accueil\GenererAccueilAbonne;
use touiteur\action\accueil\GenererAccueilTag;
use touiteur\action\accueil\GenererFooter;
use touiteur\action\accueil\GenererHeader;
use touiteur\action\accueil\GenererInfluenceurs;
use touiteur\action\accueil\GenererProfil;
use touiteur\action\GestionLike;
use touiteur\action\login\ActionLogin;
use touiteur\action\login\ActionSignin;
use touiteur\auth\Auth;
use touiteur\auth\Session;
use touiteur\classe\User;
use touiteur\db\ConnectionFactory;
use touiteur\exception\InvalideTypePage;
use touiteur\render\BaseFactory;
use touiteur\render\page\RenderPresentationProfil;
use touiteur\classe\Tag;


/**
 * Classe qui permet de dispatcher les requêtes
 */
class Dispatcher{

    /**
     * @param string $page le type de page à afficher
     * @return void affiche la page demandée
     * @throws InvalideTypePage si le type de page n'est pas valide
     * Méthode qui permet de dispatcher les requêtes
     */
	public function run(string $page): void{
		switch ($page){
			// afficher les touite decroissant
			case TYPE_PAGE_ACCUEIL:

				if (!in_array("data", array_keys($_GET))) {
					$_GET["data"] = "accueil";
				}
				if (!in_array("page", array_keys($_GET))) {
					Dispatcher::redirection("accueil?page=1");
				}



				$htmlHeader = GenererHeader::execute();
				$htmlFooter = GenererFooter::execute();
				switch ($_GET["data"]){
					case "accueil":
						$htmlHeader = GenererHeader::execute();
						$htmlMain = GenererAccueil::execute();
						$htmlFooter = GenererFooter::execute();
						break;

					case "tag":

						if (empty($_SESSION)){
							Dispatcher::redirection("login");
							break;
						}
						$htmlHeader = GenererHeader::execute();
						$htmlMain = GenererAccueilTag::execute($_SESSION["username"]);
						$htmlFooter = GenererFooter::execute();
						break;


					case "abonne":
						if (empty($_SESSION)){
							Dispatcher::redirection("login");
							break;
						}
						$htmlHeader = GenererHeader::execute();
						$htmlMain = GenererAccueilAbonne::execute($_SESSION["username"]);
						$htmlFooter = GenererFooter::execute();
						break;


					default:
						Dispatcher::redirection("accueil");
						break;

				}
				$htmlPagination = GenererPagination::execute();
				include 'src/vue/accueil.html';
				break;

			case TYPE_PAGE_PROFIL:

				global $parts;
				$username = $parts[1];
                // On vérifie bien que l'utilisateur existe dans la bd
				if (Auth::usernameExists($username)) {
					$htmlHeader = GenererHeader::execute();

					// On a bien vérifié que l'username est bon donc on peut afficher le profil de l'utilisateur demandé
					$htmlMain = GenererProfil::execute($username);
					$htmlProfil = (new RenderPresentationProfil())->render();
					$htmlFooter = GenererFooter::execute();

					include 'src/vue/profil.html';
				}
				break;

			case TYPE_PAGE_TOUIT:
				$htmlHeader = GenererHeader::execute();
				$htmlFooter = "";
				$htmlMain = GenererTouit::execute();

				include 'src/vue/touit.html';
				break;

			case TYPE_PAGE_LOGIN:
				$htmlHeader = GenererHeader::execute();


				// si déjà connecté ==> accueil
				if (!empty($_SESSION)){
					// redirection vers accueil
					//header("Location: /");
					Dispatcher::redirection("");
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
				//header("Location: /");
				Dispatcher::redirection("");
				break;

			case TYPE_PAGE_NOTFOUND:
                $p = PREFIXE;
				echo <<<HTML
                    <h1>404</h1>
                    <p>La page que vous demandez n'existe pas</p>
                    <a href="$p">Retour vers l'accueil</a>
                HTML;

				break;

			case TYPE_PAGE_LIKE:
				// SI SESSION VIDE, RENVOYER VERS LOGIN
				if (empty($_SESSION)){
					Dispatcher::redirection("login");
					break;
				}
				GestionLike::execute();
				$redirection = $_GET["redirect"];
				Dispatcher::redirection($redirection);
				break;

			case TYPE_PAGE_ABONNEMENT:
				// verification URL valide
				$redirection = $_GET["redirect"] ?? "";
				if (!in_array("username", array_keys($_GET))) {
					Dispatcher::redirection($redirection);
					break;
					//header("Location: /");
				}

				// SI SESSION VIDE, RENVOYER VERS LOGIN
				if (empty($_SESSION)){
					Dispatcher::redirection("login");
					break;
					//header("Location: /login");
				}

				$cible = $_GET["username"];
				$username = $_SESSION["username"];

				//verification username donne valide
				$db = ConnectionFactory::makeConnection();

				$st = $db->prepare("CALL EtreUserValide(\"{$cible}\")");
				$st->execute();
				$row = $st->fetch();

				$cibleValide = $row["nb_ligne"];
				if ($cibleValide==0){
					Dispatcher::redirection($redirection);
					//header("Location: /");
					break;
				}
				$db = ConnectionFactory::makeConnection();
				$st = $db->prepare("CALL verifierUsernameInAbonnement(\"{$username}\", \"{$cible}\")");
				$st->execute();
				$nb_ligne = $st->fetch()["nb_ligne"];
				// SI DEJA ABONNER
				if ($nb_ligne != 0){
					$db = ConnectionFactory::makeConnection();
					$db->prepare("CALL desabonnerUser(\"{$username}\", \"{$cible}\")")->execute();
				}
				// SI PAS ABONNER
				else{
					$email = User::loadUserFromUsername($username)->email;
					$emailCible = User::loadUserFromUsername($cible)->email;

					$db = ConnectionFactory::makeConnection();
					$db->prepare("CALL sabonnerUtilisateur(\"{$email}\", \"{$emailCible}\")")->execute();
				}
				Dispatcher::redirection($redirection);
				//header("Location: ./");
				break;

            case TYPE_PAGE_PUBLIER:
				if (empty($_SESSION))
					Dispatcher::redirection("login");

				ActionNouveauTouit::execute();

				$redirection = $_POST["redirect"];
				Dispatcher::redirection($redirection);
                break;

            // TODO A Supprimer tout ce qui est relatif à influenceurs car c'est une applciation à part
            case TYPE_PAGE_INFLUENCEURS:
                if (!empty($_SESSION)){
                    // redirection vers accueil
                    Dispatcher::redirection("login");
                }
                else {
                    $htmlHeader = GenererHeader::execute();
                    $htmlMain = GenererInfluenceurs::execute();
                    $htmlFooter = GenererFooter::execute();
                    include("src/vue/influenceurs.html");
                }
                break;

			case TYPE_PAGE_SUPPRIMER:
				if (empty($_SESSION))
					Dispatcher::redirection("login");

				ActionSupprimerTouit::execute();

				$redirection = $_GET["redirect"];
				Dispatcher::redirection($redirection);
				break;

			case TYPE_PAGE_TAG:
                global $parts;
                $libelleTag = $parts[2];
                $libelleTag = filter_var($libelleTag, FILTER_SANITIZE_STRING);
                // On vérifie que le tag demandé par l'utilisateur est bien un nombre (idTag)

                $db = ConnectionFactory::makeConnection();

                // On vérifie que le tag demandé par l'utilisateur existe bien dans la bd
                $query = 'SELECT COUNT(libelle) as NB_LIGNE, libelle, idTag, descriptionTag FROM Tag WHERE libelle LIKE ?';
                $st = $db->prepare($query);
                $st->bindParam(1, $libelleTag, PDO::PARAM_STR);
                $st->execute();

                $row = $st->fetch(PDO::FETCH_ASSOC);
                $nb_ligne = $row['NB_LIGNE'];

                // Si le tag existe bien, on affiche la page du tag
                if ($nb_ligne == 1) {
                    $idTag = $row["idTag"];
                    $libelleTag = $row["libelle"];
                    $descriptionTag = $row["descriptionTag"];
                    $base = BaseFactory::baseProfil(new Tag($idTag, $libelleTag, $descriptionTag));

                    $htmlHeader = GenererHeader::execute();
                    $htmlMain = $base->render();
                    $htmlFooter = "";

                    $query = 'SELECT
    Touite.idTouite,
    Touite.texte,
    Touite.date,
    Touite.notePertinence,
    Touite.nbLike,
    Touite.nbDislike,
    Touite.nbRetouite,
    Touite.nbVue,
    Utilisateur.emailUt AS emailUtilisateur,
    Utilisateur.nomUt,
    Utilisateur.prenomUt,
    Utilisateur.username
FROM
    UtiliserTag
JOIN Touite ON UtiliserTag.idTouite = Touite.idTouite
JOIN PublierPar ON Touite.idTouite = PublierPar.idTouite
JOIN Utilisateur ON PublierPar.emailUt = Utilisateur.emailUt
WHERE
    UtiliserTag.idTag = ?;
';
                    $st = $db->prepare($query);
                    $st->bindParam(1, $idTag, PDO::PARAM_STR);
                    $st->execute();

                    $row = $st->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($row as $touite){
                        $idTouite = $touite["idTouite"];
                        $texte = $touite["texte"];
                        $date = new DateTime($touite["date"]);
                        $user = $touite["username"];
                        $notePertinence = $touite["notePertinence"];
                        $nbLike = $touite["nbLike"];
                        $nbDisLike = $touite["nbDislike"];
                        $nbTouite = $touite["nbRetouite"];
                        $nbVue = $touite["nbVue"];
                        $base = BaseFactory::baseTouite(new Touite($idTouite, $texte, $date, $user, $notePertinence, $nbLike, $nbDisLike, $nbTouite, $nbVue));
                        $htmlMain .= $base->render();
                    }
                    $db = null;
                }
                else{
                    // Si ce n'est pas le cas, on le redirige vers l'accueil
                    Dispatcher::redirection("/");
                }


                include("src/vue/tags.html");
				break;

			default:
				throw new InvalideTypePage($page);
		}
	}

	public static function redirection(String $url){
		$p = PREFIXE;
		header("Location: /{$p}{$url}");
	}
}