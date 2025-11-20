<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionManger;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\VariationOptionController;
use App\Http\Controllers\VariationStockController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::group(['prefix' => 'auth', 'middleware' => 'rate.limit:5'], function () {
    // Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('logout', [AuthController::class, 'logout'])->middleware('token.validate');
    Route::get('me', [AuthController::class, 'me'])->middleware('token.validate');
    Route::post('create-user', [AuthController::class, 'createUser'])->middleware('token.validate');
    
    // Password reset routes with stricter rate limiting
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('rate.limit:3');
    Route::post('verify-otp', [AuthController::class, 'verifyOTP'])->middleware('rate.limit:5');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('rate.limit:3');

    //chnagePasswordForFirstTimeLogin
    Route::post('change-password-first-time', [AuthController::class, 'changePasswordForFirstTimeLogin'])->middleware('token.validate');
}); // max 5 attempts per minute;


Route::group(['prefix'=>'permission','middleware'=>['token.validate']],function(){
    Route::post('create-role',[PermissionManger::class,'createRole']);
    Route::post('assign-permission-to-role',[PermissionManger::class,'assignPermissionToRole']);
    Route::get('get-role-with-permissions',[PermissionManger::class,'getRoleWithPermissions']);
});

Route::group(['prefix'=>'users','middleware'=>['token.validate','rate.limit:10']],function(){
    Route::get('',[UserController::class,'index']);
});

// Categories Routes
Route::group(['prefix'=>'categories','middleware'=>['token.validate','rate.limit:10']],function(){
    Route::get('',[CategoryController::class,'index']);
    Route::get('{id}',[CategoryController::class,'show']);
    Route::post('',[CategoryController::class,'store']);
    Route::put('{id}',[CategoryController::class,'update']);
    Route::delete('{id}',[CategoryController::class,'destroy']);
});

// Sub Categories Routes
Route::group(['prefix'=>'sub-categories','middleware'=>['token.validate','rate.limit:10']],function(){
    Route::get('',[SubCategoryController::class,'index']);
    Route::get('{id}',[SubCategoryController::class,'show']);
    Route::post('',[SubCategoryController::class,'store']);
    Route::put('{id}',[SubCategoryController::class,'update']);
    Route::delete('{id}',[SubCategoryController::class,'destroy']);
});

// Sellers Routes
Route::group(['prefix'=>'sellers','middleware'=>['token.validate','rate.limit:10']],function(){
    Route::get('',[SellerController::class,'index']);
    Route::get('{id}',[SellerController::class,'show']);
    Route::post('',[SellerController::class,'store']);
    Route::put('{id}',[SellerController::class,'update']);
    Route::delete('{id}',[SellerController::class,'destroy']);
});

// Products Routes
Route::group(['prefix'=>'products','middleware'=>['token.validate','rate.limit:10']],function(){
    Route::get('',[ProductController::class,'index']);
    Route::get('{id}',[ProductController::class,'show']);
    Route::post('',[ProductController::class,'store']);
    Route::put('{id}',[ProductController::class,'update']);
    Route::delete('{id}',[ProductController::class,'destroy']);
});

// Variations Routes
Route::group(['prefix'=>'variations','middleware'=>['token.validate','rate.limit:10']],function(){
    Route::get('',[VariationController::class,'index']);
    Route::get('{id}',[VariationController::class,'show']);
    Route::post('',[VariationController::class,'store']);
    Route::put('{id}',[VariationController::class,'update']);
    Route::delete('{id}',[VariationController::class,'destroy']);
});

// Variation Options Routes
Route::group(['prefix'=>'variation-options','middleware'=>['token.validate','rate.limit:10']],function(){
    Route::get('',[VariationOptionController::class,'index']);
    Route::get('{id}',[VariationOptionController::class,'show']);
    Route::post('',[VariationOptionController::class,'store']);
    Route::put('{id}',[VariationOptionController::class,'update']);
    Route::delete('{id}',[VariationOptionController::class,'destroy']);
});

// Variation Stocks Routes
Route::group(['prefix'=>'variation-stocks','middleware'=>['token.validate','rate.limit:10']],function(){
    Route::get('',[VariationStockController::class,'index']);
    Route::get('{id}',[VariationStockController::class,'show']);
    Route::post('',[VariationStockController::class,'store']);
    Route::put('{id}',[VariationStockController::class,'update']);
    Route::delete('{id}',[VariationStockController::class,'destroy']);
    Route::post('{id}/update-quantity',[VariationStockController::class,'updateQuantity']);
});
