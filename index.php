<?php

class Flight
{
    private $flightNumber;
    private $cia;
    private $departureAirport;
    private $arrivalAirport;
    private $departureTime;
    private $arrivalTime;
    private $valorTotal;

    //Define new Additional services category
    private $luggages;
    private $livingLuggages;

    public function __construct(
        string $flightNumber,
        string $cia,
        string $departureAirport,
        string $arrivalAirport,
        DateTime $departureTime,
        DateTime $arrivalTime,
        float $valorTotal,
        AdditionalService $luggage = null,
        AdditionalService $livingLuggage = null
    ) {
        $this->flightNumber = $flightNumber;
        $this->cia = $cia;
        $this->departureAirport = $departureAirport;
        $this->arrivalAirport = $arrivalAirport;
        $this->departureTime = $departureTime;
        $this->arrivalTime = $arrivalTime;
        $this->valorTotal = $valorTotal;

        //Includes additional services at the time of booking
        $this->luggages = [];
        if (isset($luggage)) {
            array_push($this->luggages, $luggage);
        }

        $this->livingLuggages = [];
        if (isset($livingLuggage)) {
            array_push($this->livingLuggages, $livingLuggage);
        }
    }

    //Add additional service to flight
    public function addService(AdditionalService $newService, $additionalServiceName)
    {
        if ($additionalServiceName == 'luggage') {
            array_push($this->luggages, $newService);
        } else {
            array_push($this->livingLuggages, $newService);
        }
    }

    public function getFlightNumber()
    {
        return $this->flightNumber;
    }

    public function getCia()
    {
        return $this->cia;
    }

    public function getDepartureAirport()
    {
        return $this->departureAirport;
    }

    public function getArrivalAirport()
    {
        return $this->arrivalAirport;
    }

    public function getDepartureTime()
    {
        return $this->departureTime;
    }

    public function getArrivalTime()
    {
        return $this->arrivalTime;
    }

    public function getValorTotal()
    {
        return $this->valorTotal;
    }

    // get additional services data
    public function getAdditionalLuggageData()
    {
        $totalLuggages = 0;
        $totalCost = 0;
        foreach ($this->luggages as $luggage) {
            $totalLuggages += $luggage->getQuantity();
            $totalCost += $luggage->getPrice() * $luggage->getQuantity();
        }

        return [
            'Bagagens' => $totalLuggages,
            'Total' => $totalCost,
        ];
    }

    // get additional services data
    public function getAdditionalLivingLuggageData()
    {
        $totalLuggages = 0;
        $totalCost = 0;
        foreach ($this->livingLuggages as $luggage) {
            $totalLuggages += $luggage->getQuantity();
            $totalCost += $luggage->getPrice() * $luggage->getQuantity();
        }

        return [
            'Carga Viva' => $totalLuggages,
            'Total' => $totalCost,
        ];
    }
}

class Checkout
{
    private $flightOutbound;
    private $flightInbound;

    public function __construct(Flight $flightOutbound, Flight $flightInbound = null)
    {
        $this->flightOutbound = $flightOutbound;
        $this->flightInbound = $flightInbound;
    }

    //Add additional service to inbound/outbound flights with different prices
    public function addAdditionalServices(AdditionalService $newService, string $serviceName,  float $addInboundValue = null)
    {
        $this->flightOutbound->addService($newService, $serviceName);

        if (isset($addInboundValue)) {
            $serviceInbound = $newService->setInboundPrice($addInboundValue);
            $this->flightInbound->addService($serviceInbound, $serviceName);
        }
    }

    public function generateExtract()
    {
        $valorTotal = $this->flightOutbound->getValorTotal();
        $luggages = $this->flightOutbound->getAdditionalLuggageData();
        $livingLuggages = $this->flightOutbound->getAdditionalLivingLuggageData();
        $flightDetailsOutbound = [
            'De' => $this->flightOutbound->getDepartureAirport(),
            'Para' => $this->flightOutbound->getArrivalAirport(),
            'Embarque' => $this->flightOutbound->getDepartureTime()->format('d/m/Y H:i'),
            'Desembarque' => $this->flightOutbound->getArrivalTime()->format('d/m/Y H:i'),
            'Cia' => $this->flightOutbound->getCia(),
            'Valor' => $this->flightOutbound->getValorTotal() + $luggages['Total'] + $livingLuggages['Total'],
            'Bagagens' => $luggages['Bagagens'],
            'CargaViva' =>   $livingLuggages['Carga Viva'],
        ];
        $valorTotal += $luggages['Total'] + $livingLuggages['Total'];

        $flightDetailsInbound = [];
        if (!is_null($this->flightInbound)) {
            $luggagesInbound = $this->flightInbound->getAdditionalLuggageData();
            $livingLuggagesInbound = $this->flightInbound->getAdditionalLivingLuggageData();

            $valorTotal += $this->flightInbound->getValorTotal() + $luggagesInbound['Total'] + $livingLuggagesInbound['Total'];
            $flightDetailsInbound = [
                'De' => $this->flightInbound->getDepartureAirport(),
                'Para' => $this->flightInbound->getArrivalAirport(),
                'Embarque' => $this->flightInbound->getDepartureTime()->format('d/m/Y H:i'),
                'Desembarque' => $this->flightInbound->getArrivalTime()->format('d/m/Y H:i'),
                'Cia' => $this->flightInbound->getCia(),
                'Valor' => $this->flightInbound->getValorTotal() + $luggagesInbound['Total'] + $livingLuggagesInbound['Total'],
                'Bagagens' => $luggagesInbound['Bagagens'],
                'CargaViva' =>   $livingLuggagesInbound['Carga Viva'],
            ];
        };

        return (object) [
            'flightOutbound' => $flightDetailsOutbound,
            'flightInbound' => $flightDetailsInbound,
            'valorTotal' => $valorTotal
        ];
    }
}

//new class to handle additional services
class AdditionalService
{
    private $quantity;
    private $unitPrice;

    function __construct(float $quantity = 0, float $unitPrice = 0)
    {
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }

    public function setInboundPrice($newPrice)
    {
        $serviceCopy = new AdditionalService($this->quantity, $newPrice);
        return $serviceCopy;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getPrice()
    {
        return $this->unitPrice;
    }
}


$servicoADD = new AdditionalService(10, 38.54);

$vooTeste = new Flight(
    'AVV-9867',
    'EU MSM',
    'CNF',
    'GUA',
    new DateTime(),
    new DateTime(),
    0,
    $servicoADD,
);

$checkout = new Checkout($vooTeste, $vooTeste);

echo "TESTANDO O CODIGO<br><pre>";
print_r($checkout->generateExtract());
echo "</pre>";
