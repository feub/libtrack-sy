<?php

namespace App\Controller;

use App\Form\ScanType;
use App\Entity\Release;
use App\Form\ReleaseType;
use App\Service\MusicBrainzService;
use App\Repository\ReleaseRepository;
use App\Service\CoverArtArchiveService;
use App\Service\DiscogsService;
use App\Service\ReleaseService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/release', name: 'release.')]
#[IsGranted('ROLE_ADMIN', message: 'You need admin privileges to view this page.')]
final class ReleaseController extends AbstractController
{
    private $coverDir;

    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private HttpClientInterface $client,
        private SluggerInterface $slugger
    ) {
        $this->coverDir = $params->get('cover_dir');
    }

    #[Route('/', name: 'index')]
    public function index(ReleaseRepository $releaseRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $releases = $releaseRepository->paginatedReleases($page, $limit);
        $maxpage = ceil($releases->count() / 2);

        return $this->render('release/index.html.twig', [
            'releases' => $releases,
            'maxPage' => $maxpage,
            'page' => $page,
            'coverDir' => $this->coverDir,
        ]);
    }

    #[Route('/scan', name: 'scan', methods: ['GET', 'POST'])]
    public function scan(
        Request $request,
        DiscogsService $discogsService,
    ): Response {
        $barcodeValue = [];
        $releases = null;

        $form = $this->createForm(ScanType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $barcodeValue = $form->get('barcode')->getData();

            try {
                $releases = $discogsService->getReleaseByBarcode($barcodeValue);
            } catch (\Exception $e) {
                return $this->json([
                    'error' => $e->getMessage()
                ], 500);
            }

            // Store the data in the session
            $session = $request->getSession();
            $session->set('barcode', $barcodeValue);
            $session->set('releases', $releases);

            // Redirect to the same route
            return $this->redirectToRoute('release.scan');
        }

        // Get any data from the session
        $session = $request->getSession();
        $barcodeValue = $session->get('barcode', '');
        $releases = $session->get('releases', null);

        // Clear the session data after retrieving it
        if ($request->getMethod() === 'GET' && !$form->isSubmitted()) {
            $session->remove('barcode');
            $session->remove('releases');
        }

        return $this->render('release/scan.html.twig', [
            'form' => $form,
            'barcode' => $barcodeValue,
            'releases' => $releases
        ]);
    }

    #[Route('/scan/add', name: 'scan.add', methods: ['POST'])]
    public function scanAdd(
        Request $request,
        ReleaseService $releaseService
    ): Response {
        // Get the release ID and barcode from the form submission
        $releaseId = $request->request->get('release_id');
        $barcode = $request->request->get('barcode');

        if (!$releaseId) {
            $this->addFlash('error', 'No release ID provided.');
            return $this->redirectToRoute('scan');
        }

        if (!$barcode) {
            $this->addFlash('error', 'No barcode provided.');
            return $this->redirectToRoute('scan');
        }

        try {
            $release = $releaseService->addRelease($releaseId, $barcode);
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('release.scan');
        }

        $this->addFlash('success', 'Release "' . $release->getTitle() . '" added successfully.');
        return $this->redirectToRoute('release.scan');
    }

    #[Route('/{barcode}/fetch-cover', name: 'fetch-cover', methods: ['GET', 'POST'], requirements: ['barcode' => '\d+'])]
    public function fetchCover(
        string $barcode,
        Request $request,
        MusicBrainzService $musicBrainzService,
        CoverArtArchiveService $coverService
    ) {
        try {
            $releaseData = $musicBrainzService->getReleaseByBarcode($barcode);
            $releases = $releaseData["releases"];
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }

        // Attach cover art
        foreach ($releases as $key => $release) {
            $coverUrl = $coverService->getCoverArtByMbid($release['id']);
            $release['cover'] = $coverUrl;
            $releases[$key] = $release;
        }

        return $this->render('release/fetch-cover.html.twig', [
            'releases' => $releases,
            'barcode' => $barcode,
            'page' => $request->query->get('page', 1)
        ]);
    }

    #[Route('/update-cover', name: 'update.cover', methods: ['POST'])]
    public function updateCover(
        Request $request,
        EntityManagerInterface $em,
        ReleaseRepository $releaseRepository,
        MusicBrainzService $musicBrainzService,
        ReleaseService $releaseService
    ): Response {
        // Get the values from the form submission
        $page = $request->request->get('page');
        $releaseId = $request->request->get('release_id');
        $barcode = (string) $request->request->get('barcode', '', \FILTER_DEFAULT);

        if (!$releaseId) {
            $this->addFlash('error', 'No release ID provided');
            return $this->redirectToRoute('release.index', ['page' => $page]);
        }

        // Fetch the complete release data
        try {
            $releaseData = $musicBrainzService->getReleaseWithCoverArt($releaseId);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }

        // Get the release
        $release = $releaseRepository->findOneBy(['barcode' => $barcode]);

        if (!$release) {
            $this->addFlash('warning', 'Release with barcode "' . $barcode . '" does not exist in the database.');
            return $this->redirectToRoute('release.index', ['page' => $page]);
        }

        if ($releaseData['cover']) {
            $coverPath = $releaseService->downloadCovertArt($releaseData['cover'], $releaseId);
            $release->setCover($coverPath);
        }

        $em->flush();

        $this->addFlash('success', 'Cover art successfully added for release "' . $release->getTitle() . '".');
        return $this->redirectToRoute('release.index', ['page' => $page]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em)
    {
        $release = new Release();
        $form = $this->createForm(ReleaseType::class, $release);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($release);
            $em->flush();
            $this->addFlash('success', 'The release has been successfully added.');
            return $this->redirectToRoute('release.index');
        }

        return $this->render('release/create.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    public function edit()
    {
        //
    }

    #[Route('/{id}/confirm-delete', name: 'delete', methods: ['GET', 'POST'])]
    public function confirmDelete(Release $release, Request $request, EntityManagerInterface $em, ReleaseService $releaseService): Response
    {
        $form = $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'Delete',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $releaseService->deleteRelease($release);
                $this->addFlash('success', 'The release has been successfully deleted.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while deleting the release. Please try again.');
                $this->logger->error("Error deleting release: " . $e->getMessage());
            }

            return $this->redirectToRoute('release.index');
        }

        return $this->render('release/confirm_delete.html.twig', [
            'release' => $release,
            'form' => $form->createView(),
        ]);
    }
}
