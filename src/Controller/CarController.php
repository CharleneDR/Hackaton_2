<?php

namespace App\Controller;

use DatePeriod;
use DateInterval;
use App\Entity\Car;
use App\Form\CarType;
use App\Form\SearchCarType;
use App\Service\SearchCars;
use App\Repository\CarRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/car')]
class CarController extends AbstractController
{
    #[Route('/', name: 'app_car_index', methods: ['GET'])]
    public function index(CarRepository $carRepository): Response
    {
        return $this->render('car/index.html.twig', [
            'cars' => $carRepository->findAll(),
        ]);
    }

    #[Route('/search', name: 'app_car_result', methods: ['GET', 'POST'])]
    public function result(CarRepository $carRepository, Request $request, SearchCars $searchCars): Response
    {
        $form = $this->createForm(SearchCarType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = [];
            $pickupDate = $form->getData()['pick-upDate'];
            $dropoffDate = $form->getData()['drop-offDate'];
            $location = $form->getData()['pick-upLocation'];
            if ($pickupDate > $dropoffDate) {
                $errors[] = 'Drop-off date must be before pick-up date';
            }

            if ($errors != []) {
                return $this->render('car/searchCars.html.twig', [
                    'form' => $form
                ]);
            }

            $interval = \DateInterval::createFromDateString('1 day');
            $datesOfLocation = new \DatePeriod($pickupDate, $interval ,$dropoffDate);
            $unavailableDays = [];
            foreach($datesOfLocation as $day){
                $unavailableDays[] = $day;
            }
            $cars = $searchCars->findCorrespondingCars($unavailableDays, $location);


            return $this->render('car/searchCars.html.twig', [
                'form' => $form,
                'cars' => $cars
            ]);
                
        } else {
            return $this->render('car/searchCars.html.twig', [
                'form' => $form
            ]);
            }

        return $this->render('car/searchCars.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/new', name: 'app_car_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CarRepository $carRepository): Response
    {
        $car = new Car();
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $carRepository->save($car, true);

            return $this->redirectToRoute('app_car_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('car/new.html.twig', [
            'car' => $car,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_car_show', methods: ['GET'])]
    public function show(Car $car): Response
    {
        return $this->render('car/show.html.twig', [
            'car' => $car,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_car_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Car $car, CarRepository $carRepository): Response
    {
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $carRepository->save($car, true);

            return $this->redirectToRoute('app_car_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('car/edit.html.twig', [
            'car' => $car,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_car_delete', methods: ['POST'])]
    public function delete(Request $request, Car $car, CarRepository $carRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$car->getId(), $request->request->get('_token'))) {
            $carRepository->remove($car, true);
        }

        return $this->redirectToRoute('app_car_index', [], Response::HTTP_SEE_OTHER);
    }
}
