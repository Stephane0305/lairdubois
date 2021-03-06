<?php

namespace Ladb\CoreBundle\Controller\Opencutlist;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Entity\Opencutlist\Access;
use Ladb\CoreBundle\Utils\PaginatorUtils;

/**
 * @Route("/opencutlist")
 */
class OpencutlistController extends AbstractController {

	private function _createAccess(Request $request, $env, $kind) {
		$om = $this->getDoctrine()->getManager();

		$access = new Access();
		$access->setKind($kind);
		$access->setEnv($env);
		$access->setClientIp4($request->getClientIp());
		$access->setClientUserAgent($request->server->get('HTTP_USER_AGENT'));
		$access->setClientOclVersion($request->get('v'));
		$access->setClientOclLanguage($request->get('language'));
		$access->setClientSketchupLocale($request->get('locale'));

		$om->persist($access);
		$om->flush();

		return $access;
	}

	/////

	/**
	 * @Route("/", name="core_opencutlist_ew")
	 */
	public function ewAction() {
		$response = $this->redirect('https://extensions.sketchup.com/extension/00f0bf69-7a42-4295-9e1c-226080814e3e/open-cut-list');
		return $response;
	}

	/**
	 * @Route("/manifest", name="core_opencutlist_manifest")
	 * @Route("/manifest-{env}", requirements={"env" = "dev|prod"}, name="core_opencutlist_manifest_env")
	 */
	public function manifestAction(Request $request, $env = 'prod') {

		$access = $this->_createAccess($request, $env, Access::KIND_MANIFEST);

		$response = $this->redirect('https://raw.githubusercontent.com/lairdubois/lairdubois-opencutlist-sketchup-extension/master/dist/manifest'.($access->getIsEnvDev() ? '-dev' : '').'.json');
		return $response;
	}

	/**
	 * @Route("/download", name="core_opencutlist_download")
	 * @Route("/download-{env}", requirements={"env" = "dev|prod"}, name="core_opencutlist_download_env")
	 */
	public function downloadAction(Request $request, $env = 'prod') {

		$access = $this->_createAccess($request, $env, Access::KIND_DOWNLOAD);

		$response = $this->redirect('https://raw.githubusercontent.com/lairdubois/lairdubois-opencutlist-sketchup-extension/master/dist/ladb_opencutlist'.($access->getIsEnvDev() ? '-dev' : '').'.rbz');
		return $response;
	}

	/**
	 * @Route("/tutorials", name="core_opencutlist_tutorials")
	 * @Route("/tutorials-{env}", requirements={"env" = "dev|prod"}, name="core_opencutlist_tutorials_env")
	 */
	public function tutorialsAction(Request $request, $env = 'prod') {

		$access = $this->_createAccess($request, $env, Access::KIND_TUTORIALS);

		$response = $this->redirect('https://raw.githubusercontent.com/lairdubois/lairdubois-opencutlist-sketchup-extension/master/docs/json/tutorials.json');
		return $response;
	}

	/**
	 * @Route("/stats", name="core_opencutlist_stats")
	 * @Route("/stats/{page}", requirements={"page" = "\d+"}, name="core_opencutlist_stats_page")
	 * @Template("LadbCoreBundle:Opencutlist:stats.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_OPENCUTLIST')", statusCode=404, message="Not allowed (core_opencutlist_stats)")
	 */
	public function statsAction(Request $request, $page = 0) {

		$env = $request->get('env', 'prod');
		$days = intval($request->get('days', '28'));
		$continent = $request->get('continent', null);
		$language = $request->get('language', null);
		$locale = $request->get('locale', null);

		$om = $this->getDoctrine()->getManager();
		$accessRepository = $om->getRepository(Access::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $accessRepository->findPagined($offset, $limit, $env, $days, $continent, $language, $locale);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_opencutlist_stats_page', array( 'env' => $env, 'days' => $days ), $page, $paginator->count());

		$downloadsByDay = $accessRepository->countUniqueGroupByDay(Access::KIND_DOWNLOAD, $env, $days, $continent, $language, $locale);
		$manifestsByDay = $accessRepository->countUniqueGroupByDay(Access::KIND_MANIFEST, $env, $days, $continent, $language, $locale);
		$tutorialsByDay = $accessRepository->countUniqueGroupByDay(Access::KIND_TUTORIALS, $env, $days, $continent, $language, $locale);

		$downloadsByCountryCode = $accessRepository->countUniqueGroupByCountryCode(Access::KIND_DOWNLOAD, $env, $days, $continent, $language, $locale);
		$downloadsByCountry = array();
		$downloadsCount = 0;
		foreach ($downloadsByCountryCode as $row) {
			$downloadsByCountry[] = array(
				'count' => $row['count'],
				'countryCode' => $row['countryCode'],
				'country' => \Locale::getDisplayRegion('-'.$row['countryCode'], 'fr'),
			);
			$downloadsCount += $row['count'];
		}

		$manifestsByCountryCode = $accessRepository->countUniqueGroupByCountryCode(Access::KIND_MANIFEST, $env, $days, $continent, $language, $locale);
		$manifestsByCountry = array();
		$manifestsCount = 0;
		foreach ($manifestsByCountryCode as $row) {
			$manifestsByCountry[] = array(
				'count' => $row['count'],
				'countryCode' => $row['countryCode'],
				'country' => \Locale::getDisplayRegion('-'.$row['countryCode'], 'fr'),
			);
			$manifestsCount += $row['count'];
		}

		$tutorialsByCountryCode = $accessRepository->countUniqueGroupByCountryCode(Access::KIND_TUTORIALS, $env, $days, $continent, $language, $locale);
		$tutorialsByCountry = array();
		$tutorialsCount = 0;
		foreach ($tutorialsByCountryCode as $row) {
			$tutorialsByCountry[] = array(
				'count' => $row['count'],
				'countryCode' => $row['countryCode'],
				'country' => \Locale::getDisplayRegion('-'.$row['countryCode'], 'fr'),
			);
			$tutorialsCount += $row['count'];
		}

		$parameters = array(
			'env'                => $env,
			'days'               => $days,
			'continent'          => $continent,
			'language'           => $language,
			'locale'             => $locale,
			'prevPageUrl'        => $pageUrls->prev,
			'nextPageUrl'        => $pageUrls->next,
			'accesses'           => $paginator,
			'downloadsByDay'     => $downloadsByDay,
			'manifestsByDay'     => $manifestsByDay,
			'tutorialsByDay'     => $tutorialsByDay,
			'downloadsByCountry' => $downloadsByCountry,
			'manifestsByCountry' => $manifestsByCountry,
			'tutorialsByCountry' => $tutorialsByCountry,
			'downloadsCount'     => $downloadsCount,
			'manifestsCount'     => $manifestsCount,
			'tutorialsCount'     => $tutorialsCount,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Opencutlist:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

}