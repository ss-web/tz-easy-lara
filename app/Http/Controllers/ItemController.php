<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use Carbon\Carbon;

class ItemController extends Controller
{
    private $validateStore = [
        'item_name' => 'required|string|max:255',
        'current_stock' => 'required|integer',
        'item_price' => 'required|numeric',
    ];

    public function index()
    {
        $items = Item::orderBy('created_at', 'desc')->get();
        return view('welcome', compact('items'));
    }

    public function store(Request $request)
    {
        $request->validate($this->validateStore);

        $item = Item::create($request->all());

        $this->saveToJson();
        $this->saveToXml();

        $item->item_price = number_format($item->item_price, 2, '.', '');
        return response()->json($item);
    }


    private function saveToJson()
    {
        $items = Item::orderBy('created_at', 'desc')->get()->toArray();
        Storage::put('items.json', json_encode($items, JSON_PRETTY_PRINT));
    }

    private function saveToXml()
    {
        $items = Item::orderBy('created_at', 'desc')->get();

        $xml = new \SimpleXMLElement('<items />');
        foreach ($items as $item) {
            $itemXml = $xml->addChild('item');
            $itemXml->addChild('item_name', $item->item_name);
            $itemXml->addChild('current_stock', $item->current_stock);
            $itemXml->addChild('item_price', $item->item_price);
            $itemXml->addChild('created_at', $item->created_at);
            $itemXml->addChild('total_value', $item->current_stock * $item->item_price);
        }

        Storage::put('items.xml', $xml->asXML());
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->validateStore);

        $item = Item::findOrFail($id);
        $item->update($request->all());

        // Save to JSON and XML files
        $this->saveToJson();
        $this->saveToXml();

        return response()->json($item);
    }

}