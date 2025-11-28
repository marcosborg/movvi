<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageForm;
use App\Models\StandCar;
use App\Models\StandCarForm;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Car;
use App\Models\Fuel;
use App\Models\Year;
use App\Models\NewsArticle;
use App\Models\CarRentalContactRequest;
use App\Models\TransferTour;
use App\Models\TransferForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WebsiteController extends Controller
{
    public function index()
    {
        return view('website.index');
    }

    public function noticia(NewsArticle $newsArticle)
    {
        return view('website.noticia', compact('newsArticle'));
    }

    public function cms(Page $page, $slug)
    {
        if ($page->slug && $page->slug !== $slug) {
            return redirect()->route('website.cms', ['page' => $page->id, 'slug' => $page->slug], 301);
        }

        return view('website.page', compact('page'));
    }

    public function submitPageForm(Request $request, Page $page, $slug)
    {
        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string'],
            'phone'   => ['required', 'string'],
            'email'   => ['required', 'email'],
            'message' => ['nullable', 'string'],
            'rgpd'    => ['accepted'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('website.cms', ['page' => $page->id, 'slug' => $page->slug ?: $slug])
                ->withErrors($validator)
                ->withInput();
        }

        PageForm::create([
            'name'    => $request->name,
            'phone'   => $request->phone,
            'email'   => $request->email,
            'message' => $request->message,
            'rgpd'    => $request->boolean('rgpd'),
        ]);

        return redirect()->route('website.cms', ['page' => $page->id, 'slug' => $page->slug ?: $slug])
            ->with('status', 'Mensagem enviada com sucesso.');
    }

    public function stand()
    {
        $standCars = StandCar::with(['brand', 'car_model', 'fuel', 'catalogYear', 'origin', 'status', 'media'])->get();

        $brands = Brand::orderBy('name')->pluck('name', 'id');
        $models = CarModel::orderBy('name')->pluck('name', 'id');
        $years  = Year::orderBy('name')->pluck('name', 'id');
        $transmissions = StandCar::TRANSMISION_RADIO;

        return view('website.stand', compact('standCars', 'brands', 'models', 'years', 'transmissions'));
    }

    public function standCar(StandCar $standCar, $slug = null)
    {
        $standCar->load(['brand', 'car_model', 'fuel', 'catalogYear', 'origin', 'status', 'media']);

        if ($standCar->slug && $slug !== $standCar->slug) {
            return redirect()->route('website.stand.show', ['standCar' => $standCar->id, 'slug' => $standCar->slug], 301);
        }

        return view('website.stand-car', compact('standCar'));
    }

    public function submitStandCarForm(Request $request, StandCar $standCar, $slug = null)
    {
        $validator = Validator::make($request->all(), [
            'name'    => ['required', 'string'],
            'phone'   => ['required', 'string'],
            'email'   => ['required', 'email'],
            'city'    => ['nullable', 'string'],
            'message' => ['nullable', 'string'],
            'rgpd'    => ['accepted'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('website.stand.show', [$standCar->id, $standCar->slug])
                ->withErrors($validator)
                ->withInput();
        }

        StandCarForm::create([
            'car_id'  => $standCar->id,
            'name'    => $request->name,
            'phone'   => $request->phone,
            'email'   => $request->email,
            'city'    => $request->city,
            'message' => $request->message,
            'rgpd'    => $request->boolean('rgpd'),
        ]);

        return redirect()->route('website.stand.show', [$standCar->id, $standCar->slug])
            ->with('status', 'Pedido enviado com sucesso.');
    }

    public function rentals()
    {
        $cars = Car::with('media')->get();

        return view('website.rentals', compact('cars'));
    }

    public function rentalCar(Car $car, $slug = null)
    {
        $car->load('media');

        if ($car->slug && $slug !== $car->slug) {
            return redirect()->route('website.rentals.show', [$car->id, $car->slug], 301);
        }

        return view('website.rental-car', compact('car'));
    }

    public function submitRentalContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'name'   => ['required', 'string'],
            'phone'  => ['required', 'string'],
            'email'  => ['required', 'email'],
            'city'   => ['nullable', 'string'],
            'tvde'   => ['nullable', 'boolean'],
            'tvde_card' => ['nullable', 'string'],
            'message'   => ['nullable', 'string'],
            'rgpd'      => ['accepted'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        CarRentalContactRequest::create([
            'car_id'    => $request->car_id,
            'name'      => $request->name,
            'phone'     => $request->phone,
            'email'     => $request->email,
            'city'      => $request->city,
            'tvde'      => $request->boolean('tvde'),
            'tvde_card' => $request->tvde_card,
            'message'   => $request->message,
            'rgpd'      => $request->boolean('rgpd'),
        ]);

        return back()->with('status', 'Pedido enviado com sucesso.');
    }

    public function transfersTours()
    {
        $tours = TransferTour::with('media')->get();

        return view('website.transfers', compact('tours'));
    }

    public function transferTour(TransferTour $transferTour, $slug = null)
    {
        $transferTour->load('media');

        if ($transferTour->slug && $slug !== $transferTour->slug) {
            return redirect()->route('website.transfers.show', [$transferTour->id, $transferTour->slug], 301);
        }

        return view('website.transfer', compact('transferTour'));
    }

    public function submitTransferForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transfer_tour_id' => ['nullable', 'integer', 'exists:transfer_tours,id'],
            'name'             => ['required', 'string'],
            'phone'            => ['required', 'string'],
            'email'            => ['required', 'email'],
            'city'             => ['nullable', 'string'],
            'message'          => ['nullable', 'string'],
            'rgpd'             => ['accepted'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        TransferForm::create([
            'transfer_tour_id' => $request->transfer_tour_id,
            'name'             => $request->name,
            'phone'            => $request->phone,
            'email'            => $request->email,
            'city'             => $request->city,
            'message'          => $request->message,
            'rgpd'             => $request->boolean('rgpd'),
        ]);

        return back()->with('status', 'Pedido enviado com sucesso.');
    }
}
