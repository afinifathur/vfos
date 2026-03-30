<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::all();

        $totalAssetValue    = $assets->sum('current_value');
        $appreciatingAssets = collect();
        $depreciatingAssets = collect();

        foreach ($assets as $asset) {
            $difference = $asset->current_value - $asset->purchase_price;
            $percentage = $asset->purchase_price > 0
                ? ($difference / $asset->purchase_price) * 100
                : 0;

            $asset->difference = $difference;
            $asset->percentage = $percentage;

            if ($difference >= 0) {
                $appreciatingAssets->push($asset);
            } else {
                $depreciatingAssets->push($asset);
            }
        }

        return view('assets.index', compact(
            'assets',
            'totalAssetValue',
            'appreciatingAssets',
            'depreciatingAssets'
        ));
    }

    public function create()
    {
        return view('assets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required',
            'owner'          => 'required|in:afin,pacar,business',
            'description'    => 'nullable',
            'type'           => 'required',
            'purchase_price' => 'required|numeric',
            'current_value'  => 'required|numeric',
        ]);

        $validated['user_id'] = auth()->id();
        Asset::create($validated);
        return redirect()->route('assets.index')->with('success', 'Asset added successfully.');
    }

    public function edit(Asset $asset)
    {
        return view('assets.edit', compact('asset'));
    }

    public function update(Request $request, Asset $asset)
    {

        $validated = $request->validate([
            'name'           => 'required',
            'owner'          => 'required|in:afin,pacar,business',
            'description'    => 'nullable',
            'type'           => 'required',
            'purchase_price' => 'required|numeric',
            'current_value'  => 'required|numeric',
        ]);

        $asset->update($validated);
        return redirect()->route('assets.index')->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Asset deleted successfully.');
    }
}
