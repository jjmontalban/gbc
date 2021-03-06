<?php

namespace App\Http\Controllers\Api;

use App\Address;
use App\Customer;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerCollection;
use Facade\Ignition\Middleware\CustomizeGrouping;
use Illuminate\Validation\Rule;
//use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new CustomerCollection(Customer::with('addresses')->orderBy('id', 'DESC')->paginate(500));
    }

    
    /**
     * Display the customer.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       return Customer::where('id',$id)->with('addresses')->first();
    }


    /**
     * Filter the customer by province.
     *
     * @return \Illuminate\Http\Response
     */
    public function filter($province_id)
    {
        return new CustomerCollection(Customer::filterByProvince($province_id)->with('addresses')->orderBy('id','DESC')->paginate(100));
    }
    

    public function search($field,$query)
    {
        $query= trim($query);
        
        $res = new CustomerCollection(Customer::with('addresses')->where($field,'LIKE',"%$query%")->orderBy('id','DESC')->paginate(100));   

        if($field == "firstname" && $res->count() == 0){
            $res = new CustomerCollection(Customer::with('addresses')->where("lastname",'LIKE',"%$query%")->orderBy('id','DESC')->paginate(100));
            if($res->count()==0){
                return new CustomerCollection(Customer::with('addresses')->where("company",'LIKE',"%$query%")->orderBy('id','DESC')->paginate(100)); 
            }
        }

        if($field == "phone_1" && $res->count()==0){
            return new CustomerCollection(Customer::with('addresses')->where("phone_2",'LIKE',"%$query%")->orderBy('id','DESC')->paginate(100)); 
        }
        
        if($field == "cif" && $res->count()==0){
            return new CustomerCollection(Customer::with('addresses')->where("vat_number",'LIKE',"%$query%")->orderBy('id','DESC')->paginate(100)); 
        }

        return $res; 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|unique:customers',
            'phone_1' => 'required|unique:customers',
            'cif' => 'nullable|unique:customers',
            'vat_number' => 'nullable|unique:customers',
            'country_id' => 'required',
            'province_id' => 'required',
            'address' => 'required',
            'postcode' => 'required',
            'city' => 'required',
        ]);

        $customer = new Customer();
        $customer->firstname = $request->firstname;
        $customer->lastname = $request->lastname;
        $customer->email = $request->email;
        $customer->company = $request->company;
        $customer->phone_1 = $request->phone_1;
        $customer->phone_2 = $request->phone_2;
        $customer->cif = $request->cif;
        $customer->vat_number = $request->vat_number;
        $customer->save();

        $address = new Address();
        $address->alias = $request->alias;
        $address->address = $request->address;
        $address->postcode = $request->postcode;
        $address->city = $request->city;
        $address->province_id = $request->province_id;
        $address->country_id = $request->country_id;
        $address->customer_id = $customer->id;
        $address->save();

        return new CustomerCollection($customer);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /* public function show($id)
    {
        return new CustomerResource(Customer::findOrFail($id));
    } */

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrfail($id);

        $this->validate($request,[
            'firstname' => 'required',
            'email' => 'required', Rule::unique('customers')->ignore($customer->id),
            'phone_1' => 'required', Rule::unique('customers')->ignore($customer->phone_1),
            'cif' => 'nullable', Rule::unique('customers')->ignore($customer->cif),
            'vat_number' => 'nullable', Rule::unique('customers')->ignore($customer->vat_number),
            'addresses.*.country_id' => 'required',
            'addresses.*.province_id' => 'required',
            'addresses.*.address' => 'required',
            'addresses.*.postcode' => 'required',
            'addresses.*.city' => 'required',
        ]);

        $customer->firstname = $request->firstname;
        $customer->lastname = $request->lastname;
        $customer->company = $request->company;
        $customer->email = $request->email;
        $customer->phone_1 = $request->phone_1;
        $customer->phone_2 = $request->phone_2;
        $customer->cif = $request->cif;
        $customer->vat_number = $request->vat_number;
        $customer->updated_at = now();
        $customer->save();     

        //Direccion principal          
       foreach($request->addresses as $addressData){

            $address = Address::findOrfail($addressData['id']);
            $address->alias = $addressData['alias'];
            $address->address = $addressData['address'];
            $address->postcode = $addressData['postcode'];
            $address->city = $addressData['city'];;
            $address->province_id = $addressData['province_id'];
            $address->country_id = $addressData['country_id'];
            $address->customer_id = $addressData['customer_id'];
            $address->save();
        }
        
       return new CustomerCollection($customer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = Customer::findOrfail($id);
        $customer->delete();
        return new CustomerCollection($customer);
    }
}
