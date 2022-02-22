<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use \Gumlet\ImageResize;

class ProductController extends Controller
{
    // OBTENER TODOS LOS PRODUCTOS
    public function index()
    {
        $products = Product::with('product_variants')
                            ->where('deleted_at','=',null)
                            ->get();
        return response()->json([
            'products' => $products,
        ]);
    }

   
    public function create()
    {
        //
    }

       
    //GUARDAR PRODUCTO CON SUS VARIACIONES EN CASO DE TENERLAS 
    public function store(Request $request)
    {
        try {
            // VALIDACIONES
            $this->validate($request,[
                'ref'         => 'required|unique:products',
                'description' => 'required|min:3',
                'price'       => 'required',
            ],[
                'ref.required'         => 'La referencia es requerida',
                'ref.unique'           => 'La referencia esta ya en uso',
                'description.required' =>'Descripcion requerida',
                'price.required'       =>'Precio requerido',
            ]);

            // SI TODO ESTA BIEN REALIZA LO SIGUIENTE
            \DB::transaction(function () use ($request) {
                $product = New Product();

                $product->ref         = $request['ref'];
                $product->description = $request['description'];
                $product->price       = $request['price'];

                if ($request['images']) {
                    $file = $request['images'];
                    $fileData = ImageResize::createFromString(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file[0]['path'])));
                    $fileData->resize(500, 500);
                    $name = $file[0]['name'];
                    $path = public_path() . '/images/products/';
                    $success = file_put_contents($path . $name, $fileData);
                    $filename = $name;
                } else {
                    $filename = 'no-image.png';
                }
                $product->image = $filename;
                $product->save();

                if($request['variant']== true){
                    $product->variant = true;
                    
                    foreach($request['details'] as $variant){
                        $product_variants_data[] = [
                            'product_id' => $product->id,
                            'ref'        => $variant['ref'],
                            'price'      => $variant['price'],
                            'description'=> $variant['description'],
                        ];
                    }
                    ProductVariant::insert($product_variants_data);
                    $product->save();
                }

            }, 10);
            return response()->json([
                'status' => 200,
                'msg' => 'success',
                ], 200);  
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'msg' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    //MOSTRAR UN PRODUCTO
    public function show($id)
    {
        $product_data = Product::with('product_variants')
                                    ->where('id', $id)
                                    ->where('deleted_at', '=', null)
                                    ->first();
        return response()->json($product_data);
    }

    // FORMULARIO PARA EDITAR UN PRODUCTO
    public function edit($id)
    {
        //
    }

    //ACTUALIZAR PRODUCTO
    public function update(Request $request, $id)
    {
        try {
            // VALIDACIONES
            $this->validate($request,[
                'ref'         => 'required|unique:products',
                'description' => 'required|min:3',
                'price'       => 'required',
            ],[
                'ref.required'         => 'La referencia es requerida',
                'ref.unique'           => 'La referencia esta ya en uso',
                'description.required' =>'Descripcion requerida',
                'price.required'       =>'Precio requerido',
            ]);

            // SI TODO ESTA BIEN REALIZA LO SIGUIENTE
            \DB::transaction(function () use ($request, $id) {
                $product = Product::where('id', $id)
                                    ->where('deleted_at', '=', null)
                                    ->first();

                $product->ref         = $request['ref'];
                $product->description = $request['description'];
                $product->price       = $request['price'];

                if ($request['images']) {
                    $file = $request['images'];
                    $fileData = ImageResize::createFromString(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file['path'])));
                    $fileData->resize(500, 500);
                    $name = $file['name'];
                    $path = public_path() . '/images/products/';
                    $success = file_put_contents($path . $name, $fileData);
                    $filename = $name;
                } else {
                    $filename = 'no-image.png';
                }
                $imageTemporal = $product->image;
                $product->image = $filename;

                $pathIMG = public_path() . '/images/products/' . $imageTemporal;
                if (file_exists($pathIMG)) {
                    if ($product->image != 'no-image.png') {
                        @unlink($pathIMG);
                    }
                }
                $product->save();

                if($request['variant']== true){
                    $product->variant = true;
                    $product_variant = ProductVariant::where('product_id',$id)->get();
                    foreach($product_variant as $variant){
                        $product_variants_data[] = [
                            'product_id' => $product->id,
                            'ref'        => $variant['ref'],
                            'price'      => $variant['price'],
                            'description'=> $variant['description'],
                        ];
                    }
                    ProductVariant::insert($product_variants_data);
                    $product->save();
                }
                
            
            }, 10);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'msg' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    //ELIMINAR PRODUCTO ACTUALIZANDO EL DELETED AT
    public function destroy($id)
    {
        \DB::transaction(function () use ($id) {

            $product = Product::findOrFail($id);
            $product->deleted_at = Carbon::now();
            $product->save();

            $pathIMG = public_path() . '/images/products/' . $product->image;
            if (file_exists($pathIMG)) {
                if ($product->image != 'no-image.png') {
                    @unlink($pathIMG);
                }
            }

            ProductVariant::where('product_id', $id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }, 10);

        return response()->json(['success' => true]);
    }
}
