<?php

class Travel
{
    public $id;
    public $employeeName;
    public $departure;
    public $destination;
    public $price;
    public $companyId;

}

class Company
{
    public $id;
    public $name;
    public $parentId;
    public $travels=[];
    public $children=[];
    public $cost=0;
    public $internal_cost=0; // travel cost exclude children company

    // add travel to company
    public function addTravel(Travel $travel) {
        $this->travels[]=$travel;
        // add internal travel cost whenever add a new travel
        $this->internal_cost=$this->internal_cost+$travel->price;
    }

    // add child company
    public function addChild(Company $company) {

        $this->children[$company->id]=$company;
    }



}


class TestScript
{

    public function execute()
    {
        $start = microtime(true);
        // Read data from API
        $rawCompanies = json_decode(file_get_contents("https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies"),true);
        $rawTravels = json_decode(file_get_contents("https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels"),true);

        //Have company id as array index
        foreach ($rawCompanies as $index=>$rawCompany)
        {
            $rawCompanies[$rawCompany['id']]=$rawCompany;
            unset($rawCompanies[$index]);
        }

        // Create travel objects from raw array
        foreach ($rawTravels as $rawTravel)
        {
            $travel=new Travel();
            $travel->id=$rawTravel['id'];
            $travel->employeeName=$rawTravel['employeeName'];
            $travel->departure=$rawTravel['departure'];
            $travel->destination=$rawTravel['destination'];
            $travel->price=$rawTravel['price'];
            $travel->companyId=$rawTravel['companyId'];
            $travels[]=$travel;
        }

        // Create companies object from raw array
        foreach ($rawCompanies as $rawCompany)
        {
            $company=new Company();
            $company->id=$rawCompany['id'];
            $company->name=$rawCompany['name'];
            $company->parentId=$rawCompany['parentId'];
            foreach ($travels as $travel)
            {
                if($travel->companyId == $company->id)
                {
                    $company->addTravel($travel);
                }
            }
            $companies[$company->id]=$company;
        }

        // build nested company objects
        $company=$companies['uuid-1'];
        $costs=0;
        $this->buildCompanyTree($companies,$company,'uuid-1',$costs);
        $company= json_decode(json_encode($company),true);

        // calculate travel cost the whole company
        $data=$this->getChildrenFor([$company],0);
        foreach ($data as $d)
        {
            $cost=$cost+$d;
        }
        $company['cost']=$cost;
        $company['execute_time']= (microtime(true) - $start);
        echo json_encode($company);

        //echo 'Total time: '.  (microtime(true) - $start);
    }


    // Flat all children and get travel costs.
    public function getChildrenFor($ary, $id)
    {
        $results = array();
        foreach ($ary as $el)
        {
            if ($el['parentId'] == $id)
            {
                $results[] = $el['internal_cost'];
            }
            if (count($el['children']) > 0 && ($children = $this->getChildrenFor($el['children'], $id)) !== FALSE)
            {
                $results = array_merge($results, $children);
            }
        }
        return count($results) > 0 ? $results : FALSE;
    }

    public function calculate($tuple){
        $tuple = json_decode(json_encode($tuple),true);
        $cost=0;
        $data=$this->getChildrenFor([$tuple],0);
        foreach ($data as $d)
        {
            $cost=$cost+$d;
        }
        return $cost;
    }
    public function buildCompanyTree(&$inArray, $company, $companyId ) {
        foreach($inArray as $key => $tuple) {
            if($tuple->parentId == $companyId) {
                // recalculate travel cost for adding company
                $tuple->cost=$this->calculate($tuple);
                // recalculate travel cost for parent company
                $company->cost=$this->calculate($company);
                $company->addChild($tuple);
                $this->buildCompanyTree($inArray, $company->children[$tuple->id], $tuple->id);
            }
        }
    }
} 

(new TestScript())->execute();
