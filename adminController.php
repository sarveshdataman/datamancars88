<?php
	namespace App\Http\Controllers\Admin;	
	//namespace App\Http\Controllers\Admin\Session;
	use Illuminate\Support\Facades\Storage;
	use App\Helpers\myHelper;

	use Intervention\Image\Facades\Image;
	use App\car;
	use App\cars_image;
	use App\news;
	use App\feature_list;
 	use App\newsletter;
	use App\Http\Controllers\Controller;
	use Excel;
	
	// use App\registration;
	use Illuminate\Http\Request;
	use App\Http\Requests\carRequest;
	use Illuminate\Http\UploadedFile;
	use DB;
	use Illuminate\Html\HtmlFacade;
	use File;
	use Auth;


	//use Auth;
	
	class adminController extends Controller
	{
		/**
		 * Show the profile for the given user.
		 *
		 * @param  int  $id
		 * @return Response
		 */
		
		public function index(){	
			
			$user = DB::table('users')->get()->where('role','User');
			$total_user = count($user);	
			$cars = DB::table('cars')->get()->where('is_deleted','no');
			$sale_cars = DB::table('cars')->get()->where('is_deleted','no')->where('is_sold','sold');
			$purchase_cars = DB::table('cars')->get()->where('is_deleted','no')->where('is_sold','delivered');	
			
			
			$total_car = count($cars);		
			$reservations = DB::table('reservations')->get()->where('is_deleted','no');			
			$total_reservation = count($reservations);		
			$news = DB::table('news')->get()->where('is_deleted','no');	
			$total_news = count($news);	
			$total_order=myHelper::compare($reservations);
			$total_sales=myHelper::comparePrice($sale_cars,'sales');
			$total_purchase=myHelper::comparePrice($purchase_cars,'purchase');
			$total_inventory = myHelper::comparePrice($cars,'inventory');
			
			$purchased_chart = myHelper::chart('cars',"is_sold='delivered' && is_deleted='no'");
			$booking = myHelper::chart('reservations',"is_deleted='no'");
			$sales = myHelper::chart('cars',"is_sold='sold' && is_deleted='no'");
			
			
			$reservation_pending = DB::table('reservations')->join('cars', 'reservations.car_id', '=', 'cars.id')->select('reservations.id','reservations.name','reservations.is_reserved','cars.maker','cars.model','cars.price')->where("reservations.is_deleted","no")->where("reservations.is_reserved","pending")->get();
			
			$brandWiseInventory = DB::select("SELECT maker,COUNT(*) as count FROM cars GROUP BY maker");
			//dd($total_inventory);			
				$data = array(			
					'users' => $total_user,
					'cars' 	=> $total_car,			
					'reservations'	=> $total_reservation,	
					'news' => $total_news,
					'today_reservation'=> $total_order['today'],
					'percentage_order' => $total_order['percentage_order'],
					'sales'=> $total_sales,
					'purchase'=>$total_purchase,
					'inventory' => $total_inventory,
					'purchased_chart'=> $purchased_chart,
					'booking_chart'=>$booking,
					'sales_chart'=>$sales,
					'brand_wise_inentory'=>$brandWiseInventory,
					'reservation_pending'=>$reservation_pending,
				);
				return view('admin.adminDashboard')->withData($data);
		}

		public function addNewCar(){
			$maker = DB::table('cars')->distinct()->select('maker')->get();
			$maker_model = DB::table('cars')->distinct()->select('model')->get();
			$media = DB::table('media')->select('*')->orderBy('updated_at', 'desc')->get();
			return view('admin.adminPages.addNewCar')->withData($maker)->withModel($maker_model)->withMedia($media);
		}

		public function addNewPage(){
			return view('admin.adminPages.addNewPage');
		}

		public function saveCar(Request $request){
			$makerInput 		= $request->makerInput;
			$modelInput 		= $request->modelInput;
			$bodyInputSelect 	= $request->bodyInputSelect;
			$vpInput 			= $request->vpInput;
			$purchasePrice		= $request->purchasePrice;
			$listPrice			= $request->listPrice;
			$reservedPrice		= $request->reservedPrice;
			$yearInput 			= $request->yearInput;
			$milesInput 		= $request->milesInput;
			$show_mileage		= $request->show_mileage;
			$measureTypeInput 	= $request->measureTypeInput;
			//$doorInput 		= $request->doorInput;
			$featuredInput 		= $request->featuredInput;
			//$energyInput 		= $request->energyInput;
			//$feature 			= implode(",",$request->feature);
			$engSize 			= $request->engSize;
			$purchaser 			= $request->purchaser;
			$source				= $request->source;
			$telemarketer 		= isset($request->telemarketer)?implode(',', $request->telemarketer):'';
			$engTransmisson 	= $request->engTransmisson;
			$engFuel 			= $request->engFuel;
			$engBody 			= $request->engBody;
			$engPreviousOwner 	= $request->engPreviousOwner;
			$engLastService 	= $request->engLastService;
			$engMOT 			= $request->engMOT;
			$newEngDate 		= date("Y-m-d", strtotime($engMOT));
			$engPower 			= $request->engPower;
			$content 			= $request->content;
			$videoURL           = $request->videoURL;
			$roadTax			= $request->roadTax;
			$depreciation		= $request->depreciation;
			$regDate			= $request->regDate;
			$newRegDate 		= date("Y-m-d", strtotime($regDate));
			$engCategory		= $request->engCategory;
			$engAvailability	= $request->engAvailability;
			$show_quota			= $request->show_quota;
			$coe 				= $request->coe;
			$omv			 	= $request->omv;
			$arf 				= $request->arf;
			$sale_lease 		= $request->sale_lease;
			$ppw1				= $request->ppw1;
			$ppw2				= $request->ppw2;
			$ppw3				= $request->ppw3;
			$manufactured		= $request->manufactured;
			$cw					= $request->cw;
			$dreg				= $request->dreg;
			if(isset($request->salesConsultant) && !empty($request->salesConsultant)){
				$salesConsultant	= implode(',', $request->salesConsultant);
			}else{
				$salesConsultant	= "";
			}
			if(isset($request->consultantnumber) && !empty($request->consultantnumber)){
				$salesConsultantnumber	= implode(',', $request->consultantnumber);
			}else{
				$salesConsultantnumber	= "";
			}
			$engAccessories		= $request->engAccessories;
			$features 			= $request->features;
			

			$cars = new Car;
			$cars->maker		= !empty($makerInput)?$makerInput:'NULL';
			$cars->model 		= !empty($modelInput)?$modelInput:'NULL';
			$cars->body_type 	= !empty($bodyInputSelect)?$bodyInputSelect:'NULL';
			$cars->price 		= !empty($vpInput)?$vpInput:'NULL';
			$cars->purchase_pr	= !empty($purchasePrice)?$purchasePrice:'NULL';
			$cars->list_pr 		= !empty($listPrice)?$listPrice:'NULL';
			$cars->reserved_pr	= !empty($reservedPrice)?$reservedPrice:'';
			$cars->year         = !empty($yearInput)?$yearInput:'NULL';
			$cars->miles		= !empty($milesInput)?$milesInput:'NULL';
			$cars->show_mileage = !empty($show_mileage)?$show_mileage:'no';
			$cars->measure_type = $measureTypeInput;
			//$cars->doors 	 	= $doorInput;
			$cars->featured     = $featuredInput;
			//$cars->eco  		= $energyInput;
			$cars->vin 		 	= "123";
			$cars->car_features = !empty($features)?$features:'NULL';
			$cars->engine_size 	= !empty($engSize)?$engSize:'NULL';
			$cars->purchaser 	= !empty($purchaser)?$purchaser:'NULL';
			$cars->source 		= !empty($source)?$source:'NULL';
			$cars->telemarketer = !empty($telemarketer)?$telemarketer:'NULL';
			$cars->fuel 		= !empty($engFuel)?$engFuel:'NULL';
			$cars->color 		= !empty($engBody)?$engBody:'NULL';
			$cars->prev_owners 	= !empty($engPreviousOwner)?$engPreviousOwner:'NULL';
			$cars->last_service = !empty($engLastService)?$engLastService:'NULL';
			$cars->mot 			= !empty($newEngDate)?$newEngDate:'';
			$cars->engine_power_kw 		= !empty($engPower)?$engPower:'0';
			$cars->transmission_type 	= !empty($engTransmisson)?$engTransmisson:'NULL';
			$cars->html_content 		= !empty($content)?$content:'NULL';
			$cars->video_url            = !empty($videoURL)?$videoURL:'NULL';
			//$cars->is_sold              = "no";
			$cars->is_deleted           = "no";
			$cars->is_show              = "yes";
			$cars->roadTax			 	= !empty($roadTax)?$roadTax:'NULL';
			$cars->depreciation		 	= !empty($depreciation)?$depreciation:'NULL';
			$cars->regDate		 		= !empty($newRegDate)?$newRegDate:'NULL';
			$cars->engCategory		 	= !empty($engCategory)?$engCategory:'NULL';
			$cars->is_sold			 	= !empty($engAvailability)?$engAvailability:'NULL';
			$cars->show_quota			= !empty($show_quota)?$show_quota:'0';
			$cars->coe 				 	= !empty($coe)?$coe:'no';
			$cars->omv			 	 	= !empty($omv)?$omv:'NULL';
			$cars->arf 				 	= !empty($arf)?$arf:'NULL';
			$cars->sales_consultant 	= !empty($salesConsultant)?$salesConsultant:'NULL';	
			$cars->consultantNumber 	= !empty($salesConsultantnumber)?$salesConsultantnumber:'NULL';	
			$cars->engAccessories		= !empty($engAccessories)?$engAccessories:'NULL';
			$cars->ppw3					= !empty($ppw1)?$ppw1:'NULL';
			$cars->ppw6					= !empty($ppw2)?$ppw2:'NULL';
			$cars->ppw12				= !empty($ppw3)?$ppw3:'NULL';
			$cars->manufactured			= !empty($manufactured)?$manufactured:'NULL';
			$cars->cw					= !empty($cw)?$cw:'NULL';
			$cars->dreg					= !empty($dreg)?$dreg:'NULL';
			$cars->sale_lease			= !empty($sale_lease)?$sale_lease:'sale';
			$cars->save();

			$insertedId = $cars->id;

			$token =  session()->get('car_token');
			session()->forget('car_token');
			DB::table('cars_images')
			        ->where('car_token', $token[0])
			        ->update([
						'car_id' 	=> 	$insertedId,
						]);	
			//$request->session()->flush();
			return '<div class="alert alert-success">Scucessfully Saved</div>';
		}

		public function uploadImage(){
			//echo "Upload";
			return view('admin.adminPages.uploadImage');
		}
		
		// public function uploadImageSave(Request $request){
				// $image_id = $request->image_id;
				// $image_name  = $request->image_name;
				// // $file = $request->file('image_url');
			    // // $count = count($file);
			    // // $destinationPath = "uploads/";
			    // // if ($request->hasFile('image_url')) {
				   	// // for($i=0;$i<$count;$i++){
				    	// // $value = $file[$i];

				    	// // $filename = $value->getClientOriginalName();
				        // // $extension = $value->getClientOriginalExtension();
				        // // $picture = date('His').'_'.$filename;
				        // // $session = $request->session();
						// // $session->push('data',$picture);
						// // $img = Image::make($filename);
						// // $img->resize(intval($size), null, function($constraint) {
							 // // $constraint->aspectRatio();
						// // });
				        // // $value->storeAs($destinationPath, $picture);

				        
						// for($i=0; $i<count($image_name); $i++){
						// $cars_image = new cars_image;
				        // $cars_image->car_id = '1';
						// $cars_image->image_id = $image_id[$i];
				        // $cars_image->image_url = $image_name[$i];
				        
				        // $token =  session()->get('car_token');
				        // $cars_image->car_token = $token[0];

				        // $cars_image->save();
						// }
				    // //}
				// //}

				// // $carImages = DB::table('cars_images')->where('car_token',$token[0])->orderBy('id','desc')->get();
				// return json_encode("Success");
		// }
		public function uploadImageSave(Request $request){
				$car_id = $request->car_id;
				if(isset($car_id) && !empty($car_id)){
					$car_id1 = $car_id;
				}else{
					$car_id1 = 0;
				}
				$file_formats = array("jpg", "png", "gif", "bmp");

				$filepath = "../public/uploads/";
				$preview_width = "400";
				$preview_height = "300";

				//print_r($_FILES['image_url']);
				$total = count($_FILES['image_url']['name']);
				if($total!=0){
					for($i=0;$i<$total;$i++){
						$name = $_FILES['image_url']['name'][$i]; // filename to get file's extension
						$size = $_FILES['image_url']['size'][$i];
						
						if (strlen($name)) {
							$extension = substr($name, strrpos($name, '.')+1);
							if (in_array($extension, $file_formats)) { // check it if it's a valid format or not
								if ($size < (2048 * 1024)) { // check it if it's bigger than 2 mb or no
									$imagename = md5(uniqid() . time()) . "." . $extension;
									$tmp = $_FILES['image_url']['tmp_name'][$i];
										if (move_uploaded_file($tmp, $filepath . $imagename)) {
											//echo $imagename;
  echo $uploadDir= "../public/uploads/"; 
   $moveToDir= "../public/uploads2/"; copy($uploadDir . $imagename, $moveToDir . $imagename);
											
											$cars_image = new cars_image;

											$cars_image->car_id = $car_id1;
											$cars_image->image_url = $imagename;
											
											$token =  session()->get('car_token');
											$cars_image->car_token = $token[0];

											$cars_image->save();

											$uploadimage = $filepath.$imagename;
											$newname = $imagename;

											// Set the resize_image name
											$resize_image = $filepath.$newname;

											// It gets the size of the image
											list( $width,$height ) = getimagesize( $uploadimage );

											// It makes the new image width of 600
											$newwidth = 600;

											// It makes the new image height of 400
											$newheight = 400;

											// It loads the images we use jpeg function you can use any function like imagecreatefromjpeg
											$thumb = imagecreatetruecolor( $newwidth, $newheight );
											$source = imagecreatefromjpeg( $resize_image );

											// Resize the $thumb image.
											imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

											// It then save the new image to the location specified by $resize_image variable
											imagejpeg( $thumb, $resize_image, 100 ); 

											$out_image=addslashes(file_get_contents($resize_image));
										} else {
											echo "Could not move the file";
										}
								} else {
									echo "Your image size is bigger than 2MB";
								}
							} else {
									echo "Invalid file format";
							}
						 } else {
							echo "Please select image!";
						 } 
				}}
				$carImages = DB::table('cars_images')->where('car_token',$token[0])->where('car_id','0')->orderBy('id','desc')->get();
				return json_encode($carImages);
		}
		
		public function updateOrder(Request $request){
			$image_order = $request->data1;
			if(!empty($image_order)){
				$explode = explode(',',$image_order);
				foreach($explode as $value){
					$arr[] = explode("=",$value);
				}
				for($i=0;$i<count($arr);$i++){
					$id = $arr[$i][0];
					$order = $arr[$i][1];
					
					$query = DB::table('cars_images')
								->where('id', $id)
								->update([
									'image_order' 	=> 	$order,
								]);	
				}
			}
			return json_encode("success");
		}
		public function manageMedia(){
			$query = DB::table('media')->select('*')->get();
			return view('admin.adminPages.manageMedia')->withData($query);
		}
		// public function uploadImageSave(Request $request){

				// $file = $request->file('image_url');
			    // $count = count($file);
			    // $destinationPath = "uploads/";
			    // if ($request->hasFile('image_url')) {
				   	// for($i=0;$i<$count;$i++){
				    	// $value = $file[$i];

				    	// $filename = $value->getClientOriginalName();
				        // $extension = $value->getClientOriginalExtension();
				        // $picture = date('His').'_'.$filename;
				        // $session = $request->session();
						// $session->push('data',$picture);
						// $img = Image::make($filename);
						// $img->resize(intval($size), null, function($constraint) {
							 // $constraint->aspectRatio();
						// });
				        // $value->storeAs($destinationPath, $picture);

				        // $cars_image = new cars_image;

				        // $cars_image->car_id = '1';
				        // $cars_image->image_url = $picture;
				        
				        // $token =  session()->get('car_token');
				        // $cars_image->car_token = $token[0];

				        // $cars_image->save();
				    // }
				// }

				// $carImages = DB::table('cars_images')->where('car_token',$token[0])->orderBy('id','desc')->get();
				// return json_encode($carImages);
		// }

		public function removeImage(Request $request){
			$mode = $request->mode;
			$car_id = $request->car_id;
			if($mode=="update"){
				$query = DB::table('cars_images')->get()->where('car_id',$car_id);
				$total = count($query);
			}else{
				$total = 2;
			}
			if($total>1){
			$image_id = $request->image_id;
			$image_name = "uploads/".$request->image_name;
			unlink($image_name);
			DB::table('cars_images')
		            ->where('id', $image_id)
		            ->delete();
		    return json_encode("success",$image_id); 
			}else{
				return json_encode("no"); 
			}
		}

		public function manageCar(Request $request){

			$searchcar = $request->searchcar;
			if($searchcar!=""){
				$search = '%'.$searchcar.'%';
				$carDetails = DB::select("select * from cars where is_deleted='no' AND maker like '".$search."'  OR model like '".$search."'");
             }
             else{
             	$carDetails = DB::table('cars')->get()->where("is_deleted","no");
             }
			$imageDetails = DB::table('cars_images')->select('cars_images.id','cars_images.car_id','cars_images.image_url')->Join('cars', 'cars_images.car_id', '=', 'cars.id')->where('cars.is_deleted','no')->get();
			return view('admin.adminPages.manageCar')->withData($carDetails)->withImages($imageDetails);
		}

		public function updateCar(Request $request){
			$updateBtn  		= $request->updateBtn;
			$removeCar		 	= $request->removeCar;
			$soldCar		 	= $request->soldCar;
			$carId              = $request->carId;
			$makerInput 		= $request->makerInput;
			$modelInput 		= $request->modelInput;
			$bodyInputSelect 	= $request->bodyInputSelect;
			$vpInput 			= $request->vpInput;
			$engCategory 		= $request->engCategory;//
			$purchasePrice 		= $request->purchasePrice;//
			$listPrice 			= $request->listPrice;//
			$reservedPrice 		= $request->reservedPrice;//
			$engSize 			= $request->engSize;//
			$purchaser 			= $request->purchaser;//
			$source 			= $request->source;//
			if(isset($request->telemarketer) && !empty($request->telemarketer)){
			$telemarketer 		= implode(',',$request->telemarketer);//
			}else{
				$telemarketer   = "";
			}
			$yearInput 			= $request->yearInput;
			$milesInput 		= $request->milesInput;
			$measureTypeInput 	= $request->measureTypeInput;
			$featuredInput 		= $request->featuredInput;
			$ppw1				= $request->ppw1;
			$ppw2				= $request->ppw2;
			$ppw3				= $request->ppw3;
			$manufactured		= $request->manufactured;
			$cw					= $request->cw;
			$dreg				= $request->dreg;
			if(isset($updateBtn)){
				DB::table('cars')
			        ->where('id', $carId)
			        ->update([
						'maker' 	=> 	$makerInput,
						'model' 	=> 	$modelInput,
						'body_type'	=> 	$bodyInputSelect,
						'price' 	=> 	$vpInput,
						'purchase_pr' => $purchaser,
						'list_pr'   =>  $listPrice,
						'reserved_pr' => $reservedPrice,
						'year'		=>	$yearInput,
						'miles'		=>	$milesInput,
						'measure_type'	=>	$measureTypeInput,
						'featured'		=>	$featuredInput,
						'engine_size'   =>  $engSize,
						'purchaser'     =>  $purchaser,
						'source'		=>  $source,
						'telemarketer'	=>	$telemarketer,
						'engCategory'   =>  $engCategory,
						'ppw3'			=> $ppw1,
						'ppw6'			=> $ppw2,
						'ppw12'			=> $ppw3,
						'manufactured'	=> $manufactured,
						'cw'			=> $cw,
						'dreg'			=> $dreg,
					]);
					$message = "Updated";
			}elseif (isset($removeCar)) {
				DB::table('cars')
			        ->where('id', $carId)
			        ->update([
						'is_deleted' 	=> 	'yes',
						]);
			        $message = "Remove";
			}elseif (isset($soldCar)) {
				DB::table('cars')
			        ->where('id', $carId)
			        ->update([
						'is_sold' 	=> 	'sold',
						]);
			        $message = "Sold";
			}else{
				echo "No Task";
			}

			//return "<div class='alert alert-success'><strong>Success!</strong>$message Successfully.</div>";
			 return redirect('cadmin/manage-car')->with('status', "Success! $message Successfully.");
		}

		public function updateCarDetails(Request $request, $id){
			$maker = DB::table('cars')->distinct()->select('maker')->get();
			$maker_model = DB::table('cars')->distinct()->select('model')->get();
			$media = DB::table('media')->select('*')->orderBy('updated_at', 'desc')->get();
			$carDetails = DB::table('cars')
								->select('*')
								->where("id",$id)
								->get();
			$selectedImage = DB::table('cars_images')
								->select('id','car_id','image_url')
								->where('car_id', $id)
								->get();
			return view('admin.adminPages.updateCarDetails')->withData1($carDetails)->withData($maker)->withModel($maker_model)->withMedia($media)->withSelected($selectedImage);

		}

		public function saveUpdateCarDetails(Request $request){
			//print_r($_POST);
			$id  	= $request->carid;
			$selectregdate = DB::table('cars')->select('regDate')->where("id",$id)->get();			
				if($request->regDate==""){
					$tobesavedate = $selectregdate[0]->regDate;
				}	
				else{
					$tobesavedate = $request->regDate;
				}
			$makerInput 		= $request->makerInput;
			$modelInput 		= $request->modelInput;
			$bodyInputSelect 	= $request->bodyInputSelect;
			$vpInput 			= $request->vpInput;
			$purchasePrice		= $request->purchasePrice;
			$listPrice			= $request->listPrice;
			$reservedPrice		= $request->reservedPrice;
			$yearInput 			= $request->yearInput;
			$milesInput 		= $request->milesInput;
			$show_mileage		= $request->show_mileage;
			$measureTypeInput 	= $request->measureTypeInput;
			//$doorInput 		= $request->doorInput;
			$featuredInput 		= $request->featuredInput;
			//$energyInput 		= $request->energyInput;
			//$feature 			= implode(",",$request->feature);
			$engSize 			= $request->engSize;
			$purchaser 			= $request->purchaser;
			$source				= $request->source;
			$telemarketer 		= isset($request->telemarketer)?implode(',', $request->telemarketer):'';
			$engTransmisson 	= $request->engTransmisson;
			$engFuel 			= $request->engFuel;
			$engBody 			= $request->engBody;
			$engPreviousOwner 	= $request->engPreviousOwner;
			$engLastService 	= $request->engLastService;
			$engMOT 			= $request->engMOT;
			$newEngDate 		= date("Y-m-d", strtotime($engMOT));
			$engPower 			= $request->engPower;
			$content 			= $request->content;
			$videoURL           = $request->videoURL;
			$roadTax			= $request->roadTax;
			$depreciation		= $request->depreciation;
			$regDate			= $tobesavedate;
			$newRegDate 		= date("Y-m-d", strtotime($regDate));
			$engCategory		= $request->engCategory;
			$engAvailability	= $request->engAvailability;
			$show_quota			= $request->show_quota;
			$coe 				= $request->coe;
			$omv			 	= $request->omv;
			$arf 				= $request->arf;
			$sale_lease 		= $request->sale_lease;
			$ppw1				= $request->ppw1;
			$ppw2				= $request->ppw2;
			$ppw3				= $request->ppw3;
			$manufactured		= $request->manufactured;
			$cw					= $request->cw;
			$dreg				= $request->dreg;
			if(isset($request->salesConsultant) && !empty($request->salesConsultant)){
				$salesConsultant	= implode(',', $request->salesConsultant);
			}else{
				$salesConsultant	= "";
			}
			if(isset($request->consultantnumber) && !empty($request->consultantnumber)){
				$consultantnumber	= implode(',', $request->consultantnumber);
			}else{
				$consultantnumber	= "";
			}
			$engAccessories		= $request->engAccessories;
			$features 			= $request->features;


			$query = DB::table('cars')
		        ->where('id', $id)
		        ->update([
					'maker' 	=> 	$makerInput,
						'model' 	=> 	$modelInput,
						'body_type'	=> 	$bodyInputSelect,
						'price' 	=> 	$vpInput,
						'purchase_pr' => $purchaser,
						'list_pr'   =>  $listPrice,
						'reserved_pr' => $reservedPrice,
						'year'		=>	$yearInput,
						'show_mileage' => !empty($show_mileage)?'yes':'no',
						'miles'		=>	$milesInput,
						'measure_type'	=>	$measureTypeInput,
						'featured'		=>	$featuredInput,
						'engine_size'   =>  $engSize,
						'purchaser'     =>  $purchaser,
						'source'		=>  $source,
						'telemarketer'	=>	$telemarketer,
						'engCategory'   =>  $engCategory,
						'ppw3'			=> $ppw1,
						'ppw6'			=> $ppw2,
						'ppw12'			=> $ppw3,
						'manufactured'	=> $manufactured,
						'cw'			=> $cw,
						'dreg'			=> $dreg,
					'car_features'	=> $features,
					'fuel' 		=> $engFuel,
					'color' 		=> $engBody,
					'prev_owners' 	=> $engPreviousOwner,
					'last_service' => $engLastService,
					'mot' 			=> $engMOT,
					'engine_power_kw' 		=> $engPower,
					'transmission_type' 	=> $engTransmisson,
					'html_content' 		=> $content,
					'roadTax'	=> $roadTax,
					'depreciation'	=> $depreciation,
					'regDate'	=> $regDate,
					'show_quota'	=> !empty($show_quota)?'1':'0',
					'coe'	=> $coe,
					'omv'	=> $omv,
					'arf'	=> $arf,
					'sales_consultant' => $salesConsultant,
					'consultantNumber' => $consultantnumber,
					'engAccessories'	=> $engAccessories,
					'is_sold'	=> $engAvailability,
				
			]);
		    if($query){
		    	return '<div class="alert alert-success">Scucessfully Update</div>';
		    }else{
		    	return '<div class="alert alert-warning">Warning</div>';
		    }
		}
		
		public function updateCarMediaDetails($id){
			$selectAllImage = DB::table('media')
								->select('*')
								->get();
			$selectedImage = DB::table('cars_images')
								->select('id','car_id','image_url')
								->where('car_id', $id)->orderBy('image_order', 'asc')
								->get();
			// for($i=0; $i<count($selectedImage); $i++){
				// echo $selectedImage[$i]->id;
			// }
			// die;
			return view('admin.adminPages.updateMedia')->withData($selectAllImage)->withSelected($selectedImage)->withId($id);
		}
		
		public function updateUploadImage(Request $request){
			$car_id = $request->car_id;
			$selectedCarId = $request->selectedCarId;
			$image_id = $request->image_id;
			$image_name  = $request->image_name;
			// echo '<pre>';
			// print_r($image_name);
			// echo '</pre>';
			$selectedImage = DB::table('cars_images')
								->select('id','car_id','image_id','image_url')
								->where('car_id', $car_id)
								->get();
			$total = count($selectedImage);
			$img[] ="";
			$image_url[] ="";
			for($j=0;$j<$total; $j++){
				$img[] = $selectedImage[$j]->image_id;
				$image_url[] = $selectedImage[$j]->image_url;
			}
			if(is_array($image_name)){
				$unselectedImage = array_diff($image_url,$image_name);
				//print_r($unselectedImage);
				foreach($unselectedImage as $value){
					//echo $value;
					DB::table('cars_images')->where('image_url', '=', $value)->where('car_id', $car_id)->delete();
				}
			}else{
				return redirect('cadmin/update-carmedia-details/'.$car_id)->with('warning', 'You can not remove all images.');
			}
			if($total==0){
				for($i=0; $i<count($image_name); $i++){
					$cars_image = new cars_image;
					$cars_image->car_id = $car_id;
					$cars_image->image_id = $image_id[$i];
					$cars_image->image_url = $image_name[$i];
					
					//$token =  session()->get('car_token');
					$cars_image->car_token = "1";

					$cars_image->save();
				}
			}else{
				
					for($i=0; $i<count($image_name); $i++){
						if(!in_array($image_name[$i],$image_url)){
							$cars_image = new cars_image;
							//echo $image_name[$i];
							$cars_image->car_id = $car_id;
							$cars_image->image_id = $image_id[$i];
							$cars_image->image_url = $image_name[$i];
							$cars_image->car_token = "1";
							
							$cars_image->save();
						}
					}
			}
			
			return redirect('cadmin/update-carmedia-details/'.$car_id)->with('status', 'Your changes has been saved successfully.');
		}
		
		public function reservationManager(){
			$cars = DB::table('cars')->distinct()->join('reservations', 'reservations.car_id', '=', 'cars.id')->select('cars.id','cars.maker','cars.model','cars.price','cars.is_show','cars.sale_lease')->where("reservations.is_deleted","no")->get();
			//print_r($cars);
			$reservation = DB::table('reservations')->join('cars', 'reservations.car_id', '=', 'cars.id')->select('reservations.id','reservations.car_id','reservations.user_id','reservations.name','reservations.email','reservations.phone','reservations.country','reservations.is_reserved','reservations.is_show','reservations.created_at','cars.maker','cars.model','cars.price')->where("reservations.is_deleted","no")->get();
			
			return view('admin.adminPages.reservationManager')->withData($reservation)->withCars($cars);			
		}

		public function removeReservation(Request $request){
			if(isset($request->mode) && $request->mode=="reservations"){
				$reservation_id = isset($request->reservation_id)?$request->reservation_id:'';
				$show_car = isset($request->status)?$request->status:'pending';
			
				$query = DB::table('reservations')
					->where('id', $reservation_id)
					->update([
						'is_reserved' => $show_car,
				]);
				
				if($query){
					return json_encode("Success");
				}
			}elseif(isset($request->mode) && $request->mode=="car"){
				$car_id = isset($request->car_id)?$request->car_id:'';
				$show_car2 = isset($request->ckeck_condition)?$request->ckeck_condition:'no';
				$query = DB::table('cars')
					->where('id', $car_id)
					->update([
						'is_show' => $show_car2,
				]);
				
				if($query){
					return json_encode("Success");
				}
			}else{
				return json_encode("Error");
			}
		}

		public function ManageFeatures($id){
		   $feature_icon = DB::table('feature_lists')->get();
		   $carFeature= DB::table('cars')->select('id','car_features')->where('id', $id)->get();

		   return view('admin.adminPages.manageFeatures')->withFeature($carFeature)->withData($feature_icon);
		}

		public function UpdateFeatures(request $request,$id){
			  $feature= !empty($request->feature)?$request->feature:'';
			  if($feature!=''){
				$feature  = implode(",",$feature);
			  } else {
			  	$feature  = '';
			  }

			  DB::table('cars')
	            ->where('id', $id)
	            ->update([
					'car_features' => $feature,
	    	  ]);
			  return redirect()->back()->with('status', 'Features Updated!');
		}

			// News

		public function addNewNews(){
		  	return view('admin.adminPages.addNewNews');
		}

		public function saveNews(Request $request){
		   $newsTitle    = $request->newsTitle;
		   $newsContent  = $request->newsContent;
		  
		   $news = new News;
		   $news->news_title  = !empty($newsTitle)?$newsTitle:$newsTitle;
		   $news->news_content  = !empty($newsContent)?$newsContent:$newsContent;
		   
		   $news->save();
		   return '<div class="alert alert-dismissible alert-success">
			       <button type="button" class="close" data-dismiss="alert"><i class="fa fa-remove"></i>
			       </button> News Scucessfully Saved </div>';
		} 

	  	public function manageNews(){
		   	$allNews = DB::table('news')->where('is_deleted', 'no')->get();
		   	return view('admin.adminPages.manageNews')->withNews($allNews);
		}

	  	public function getNews(Request $request, $id){
		   $newsId=$request->newsId;
		   $allNews1 = DB::table('news')->where('id', $id)->get();

		   return view('admin.adminPages.updateNews')->withD($allNews1);
		} 

	  	public function manageNewsUpdate(Request $request,$id ) {

		  	$newsId=$request->newsId;
		  	$newsTitle= $request->newsTitle;
		  	$newsContent= $request->newsContent;
		  
		  	DB::table('news')
		        ->where('id', $id)
		        ->update([
					'news_title' => $newsTitle,
		    		'news_content' => $newsContent,
		    	]);
			return redirect('cadmin/manage-news')->with('status', 'News updated!');
		}

		public function manageNewsDelete(Request $request,$id ) {

  			DB::table('news')
				->where('id', $id)
				->update([
		            'is_deleted' => 'yes'
				]);
			return redirect('cadmin/manage-news')->with('status', 'News Deleted!');
		}

	 	public function addFeaturedImage(Request $request){
		 	$file = $request->image_url;
		    $imageForVal = "admin/image/feature_icons";
		    $iconLabel = $request->iconLabel;

		    if ($request->hasFile('image_url')) {
		    	//echo "Test";
		    	if ($request->file('image_url')->isValid()) {
					$path = $request->image_url->path();		
					$extension = $request->image_url->extension();
					$extension_array = array('png','jpg','jpeg','gif');
					if(in_array($extension, $extension_array)){
						$imageName = date('ymd').'_'.time().'.'.$extension;
						$path = $request->image_url->storeAs($imageForVal,$imageName);

						$feature_list = new feature_list;

						$feature_list->label = $iconLabel;
						$feature_list->source = "image/feature_icons/".$imageName;

						$feature_list->save();
					}
				}
			}
			return redirect('cadmin/featured-icon-manager')->with('status', 'Icon  Added Successfully!');
	 	}

	 	public function featuredIconManager(){
	 		$feature_icon = DB::table('feature_lists')->get();
	 		return view('admin.adminPages.featuredIconManager')->withData($feature_icon);
	 	}
		
		public function sale_lease_manager(){
			$saleLeaseData = DB::table('sale_lease_car')->get();
			return view('admin.adminPages.saleLeaseManager')->withData($saleLeaseData);
		}

	 	public function updateFeaturedIconManager(Request $request){
	 		$update = $request->update;
	 		$remove = $request->remove;
	 		$iconId = $request->iconId;
	 		$iconLabel = $request->iconLabel;

	 		if(isset($update)){
	 			DB::table('feature_lists')
		           	->where('id', $iconId)
		           	->update([
						'label' => $iconLabel,
		    		]);	
		    		$message = "Updated";	
	 		}elseif(isset($remove)){
	 			DB::table('feature_lists')
		            ->where('id', $iconId)
		            ->delete();
		        $message = "Removed";
	 		}else{
	 			$message = "Error";	
	 		}
	 		return redirect('cadmin/featured-icon-manager')->with('status', "featured Icon $message !");
	 	}

	 	public function showReservationPolicy(Request $request){
	 		$policyContent = DB::table('policy_reservations')->where('id','1')->get();
			return view('admin.adminPages.editReservationPolicy')->withData($policyContent);
	 	}

	 	public function UpdateReservationPolicy(Request $request){
	 		echo $policy_content = $request->policy_content;
	 		DB::table('policy_reservations')
            	->where('id', '1')
            	->update([
					'reservation_policy' => $policy_content
    			]);

	 		return redirect('cadmin/reservation-policy')->with('status', 'Policy Updated !');
	 	} 

		// News Letter

	 	public function addNewNewsletter(){
	   		return view('admin.adminPages.addNewNewsletter');
	  	}
		
		public function manageSubscriber(){
		   	$allSubs = DB::table('subscribers')->where('is_deleted', 'no')->get();
		   	return view('admin.adminPages.manageSubscriber')->withSubs($allSubs);
		}
	  
	  	public function saveNewsletter(Request $request){

		   	$this->validate($request,[
		    	'emailSubject'=> 'required',
		    	'newsletterContent'=> 'required',
		    ]);

			$emailSubject    = $request->emailSubject;
		   	$newsletterContent  = $request->newsletterContent;
		   
			$news = new Newsletter;
		   	$news->email_title  = !empty($emailSubject)?$emailSubject:$emailSubject;
		   	$news->email_content  = !empty($newsletterContent)?$newsletterContent:$newsletterContent;
		   
		   	$news->save();
		   	return redirect()->back()->with('success', ' Newsletter Sent Sucessfully!');
		}

	  	public function manageNewsletter(){
		   	$allNewsletters = DB::table('newsletters')->where('is_deleted', 'no')->get();
		   	return view('admin.adminPages.manageNewsletter')->withNewsletter($allNewsletters);
		}

		public function getNewsletter(Request $request, $id){
	   		$newsId=$request->newsId;
	   		$allNews1 = DB::table('newsletters')->where('id', $id)->get();

	   		return view('admin.adminPages.updateNewsletter')->withD($allNews1);
	  	}

	  	public function manageSubsDelete(Request $request,$id ) {

			DB::table('subscribers')
		        ->where('id', $id)
		        ->update([
					'is_deleted' => 'yes'
		    	]);

		 	return redirect('cadmin/manage-subscriber')->with('status', 'Subscriber Deleted!');
		}

		//Accounts

		public function manageAccounts(){
        	$users = DB::table('users')->where('is_deleted', 'no')->get();
        	return view('admin.adminPages.manageAccounts')->withAc($users);
       	}

        public function getAccount(Request $request, $id){
         	$newsId=$request->newsId;
         	$allNews1 = DB::table('users')->where('id', $id)->get();

         	return view('admin.adminPages.updateAccount')->withD($allNews1);
        }

   	public function manageAccountUpdate(Request $request,$id ) {

      	$name= $request->name;
       	$email= $request->email;
       
       DB::table('users')
         	->where('id', $id)
         	->update([
				'name' => $name,
				'email' => $email,
		]);

		return redirect('cadmin/manage-accounts')->with('status', 'Account updated!');
	}

	public function manageAccountDelete(Request $request,$id ) {

  		DB::table('users')
                ->where('id', $id)
                ->update([

                 'is_deleted' => 'yes'
       	]);
	   	return redirect('cadmin/manage-accounts')->with('status', 'Account Deleted!');
	}

	public function addPolicy(){
       return view('admin.adminPages.addPolicy');
    }

    public function getPolicy(Request $request, $id){
      	$allPolicy = DB::table('policies')->where('id', $id)->get();
		return view('admin.adminPages.updatePolicy')->withPolicy($allPolicy);
    }

    public function savePolicy(Request $request){

        $this->validate($request,[
         	'policyTitle'=> 'required',
        ]);


        $policyTitle    = $request->policyTitle;
        $policyContent  = $request->policyContent;
        

        $policy = new Policy;
        $policy->title  = !empty($policyTitle)?$policyTitle:$policyTitle;
        $policy->content  = !empty($policyContent)?$policyContent:$policyContent;
        

        $policy->save();
        return redirect()->back()->with('success', ' Policy Created  Sucessfully!');
    }

    public function managePolicy(){
       $allPolicy = DB::table('policies')->where('is_deleted', 'no')->get();
       return view('admin.adminPages.managePolicy')->withPolicy($allPolicy);
    }

    public function managePolicyUpdate(Request $request,$id ) {

      
       	$policyTitle    = $request->policyTitle;
        $policyContent  = $request->policyContent;

		DB::table('policies')
         ->where('id', $id)
         ->update([
	          	'title' => $policyTitle,
	         	'content' => $policyContent,
        ]);
	    return redirect('cadmin/manage-policy')->with('status', 'Policy updated!');
	}

    public function managePolicyDelete(Request $request,$id ) {

  		DB::table('policies')
          	->where('id', $id)
          	->update([
				'is_deleted' => 'yes'
           	]);
	   	return redirect('cadmin/manage-policy')->with('status', 'Policy Deleted!');
	}  

  	public function ManageLog(){
    	$allLogs = DB::table('logs')->where('is_deleted', 'no')->get();
   		return view('admin.adminPages.manageLog')->withlogs($allLogs);
  	} 



  	public function manageLogDelete(Request $request,$id ) {

			DB::table('logs')
          	->where('id', $id)
          	->update([
	           'is_deleted' => 'yes'
           	]);
		return redirect('cadmin/manage-log')->with('status', 'Log Deleted!');
	}

	public function importExport() {
		return view('admin.adminPages.importExport');
	}


	/********************************
	* Import file into database Code
	*
	* @var array
	*******************************/

	public function importExcel(Request $request) {

	  	if($request->hasFile('import_file')){
		   	$path = $request->file('import_file')->getRealPath();
			$data = Excel::load($path, function($reader) {})->get();

		   	if(!empty($data) && $data->count()){
		   			
		   			$data2 = $data->toArray();
			   		print_r($data2);
			   		for($i=0;$i<$data->count();$i++){
			   			$insert[] = [
			   							'maker' => $data2[$i]['maker'],
			   						    'model' => $data2[$i]['model'],
			   						    'body_type' => $data2[$i]['body_type'],
			   						    'price' => $data2[$i]['price'],
			   						    'year' => $data2[$i]['year'],
			   						    'miles' => $data2[$i]['miles'],
			   						    'measure_type' => $data2[$i]['measure_type'],
			   						    'doors' => $data2[$i]['doors'],
			   						    'featured' => $data2[$i]['featured'],
			   						    'eco' => $data2[$i]['eco'],
			   						    'vin' => $data2[$i]['vin'],
			   						    'car_features' => $data2[$i]['car_features'],
			   						    'engine_size' => $data2[$i]['engine_size'],
			   						    'trim' => $data2[$i]['trim'],
			   						    'type' => $data2[$i]['type'],
			   						    'gear' => $data2[$i]['gear'],
			   						    'fuel' => $data2[$i]['fuel'],
										'color' => $data2[$i]['color'],
			   						    'prev_owners' => $data2[$i]['prev_owners'],
			   						    'last_service' => $data2[$i]['last_service'],
			   						    'mot' => $data2[$i]['mot']->toDateTimeString(),
			   						    'tax_band' => $data2[$i]['tax_band'],
			   						    'top_speed' => $data2[$i]['top_speed'],
			   						    'engine_torque_rpm' => $data2[$i]['engine_torque_rpm'],
			   						    'engine_power_kw' => $data2[$i]['engine_power_kw'],
			   						    'transmission_type' => $data2[$i]['transmission_type'],
			   						    'html_content' => $data2[$i]['html_content'],
			   						    'video_url' => $data2[$i]['video_url'],
			   						    'image_url' => $data2[$i]['image_url'],
			   						    'roadTax'	=> $data2[$i]['road_tax'],
			   						    'depreciation'	=> $data2[$i]['depreciation'],
			   						    'regDate'	=> $data2[$i]['reg_date'],
			   						    'engMileage'	=> $data2[$i]['eng_mileage'],
			   						    'engCategory'	=> $data2[$i]['eng_category'],
			   						    'coe'	=> $data2[$i]['coe'],
			   						    'omv'	=> $data2[$i]['omv'],
			   						    'arf'	=> $data2[$i]['arf'],
			   						    'engAccessories'	=> $data2[$i]['eng_accessories'],
			   						    'is_sold' => $data2[$i]['is_sold'],
			   						    'is_deleted' => 'no',
			   						    'created_at' => date('y-m-d'),
			   						    'updated_at' => date('y-m-d'),
			   						];
			   		}
					if(!empty($insert)){
				     	Car::insert($insert);
				     	return back()->with('success','Insert Record successfully.');
				    }
			}
		}
		return back()->with('error','Please Check your file, Something is wrong there.');
	}
	
	public function saveUplaod(){
		$file_formats = array("jpg", "png", "gif", "bmp");

		$filepath = "../public/uploadImage/upload_images/";
		$preview_width = "400";
		$preview_height = "300";


		if (isset($_POST['submitbtn']) && $_POST['submitbtn']=="Upload") {

		 $name = $_FILES['imagefile']['name']; // filename to get file's extension
		 $size = $_FILES['imagefile']['size'];

		 if (strlen($name)) {
			$extension = substr($name, strrpos($name, '.')+1);
			if (in_array($extension, $file_formats)) { // check it if it's a valid format or not
				if ($size < (2048 * 1024)) { // check it if it's bigger than 2 mb or no
					$imagename = md5(uniqid() . time()) . "." . $extension;
					$tmp = $_FILES['imagefile']['tmp_name'];
						if (move_uploaded_file($tmp, $filepath . $imagename)) {
							echo $imagename;
						} else {
							echo "Could not move the file";
						}
				} else {
					echo "Your image size is bigger than 2MB";
				}
			} else {
					echo "Invalid file format";
			}
		 } else {
			echo "Please select image!";
		 }
		 exit();
		}
	}
	
	public function resizeThumbnailImage($thumb_image_name, $image, $width, $height, $start_width, $start_height, $scale){

		list($imagewidth, $imageheight, $imageType) = getimagesize($image);
		$imageType = image_type_to_mime_type($imageType);
		
		$newImageWidth = ceil($width * $scale);
		$newImageHeight = ceil($height * $scale);
		$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
		switch($imageType) {
			case "image/gif":
				$source=imagecreatefromgif($image); 
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$source=imagecreatefromjpeg($image); 
				break;
			case "image/png":
			case "image/x-png":
				$source=imagecreatefrompng($image); 
				break;
		}
		imagecopyresampled($newImage,$source,0,0,$start_width,$start_height,$newImageWidth,$newImageHeight,$width,$height);
		switch($imageType) {
			case "image/gif":
				imagegif($newImage,$thumb_image_name); 
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				imagejpeg($newImage,$thumb_image_name,100); 
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage,$thumb_image_name);  
				break;
		}
		chmod($thumb_image_name, 0777);
		return $thumb_image_name;
	}
	public function uploadImageHere(){
		$upload_path = "../public/uploadImage/upload_images/";				
		$upload_path1 = "../public/uploads/"; 						
		$thumb_width = "600";						
		$thumb_height = "400";
		if (isset($_POST["upload_thumbnail"])) {

			$filename = $_POST['filename'];

			$large_image_location = $upload_path.$_POST['filename'];
			$thumb_image_location = $upload_path1."thumb_".$_POST['filename'];
			$thumb_image_name = "thumb_".$_POST['filename'];
			$x1 = $_POST["x1"];
			$y1 = $_POST["y1"];
			$x2 = $_POST["x2"];
			$y2 = $_POST["y2"];
			$w = $_POST["w"];
			$h = $_POST["h"];
			
			$value = array(
				'img_name'=>$thumb_image_name,
				'created_at'=>date('Y-m-d h:i:s'),
				'updated_at'=>date('Y-m-d h:i:s')
				);
			$query = DB::table('media')->insert($value);
			if (!$w) {
				return redirect('upload-image')->with('warning', 'Please crop image');
			}else{
				$scale = $thumb_width/$w;
				$cropped = $this->resizeThumbnailImage($thumb_image_location, $large_image_location,$w,$h,$x1,$y1,$scale);
			
				return redirect('upload-image')->with('success', 'image uploaded successfully');
			}
			
		}
	}
	public function uploadImageHtml(){
		
		return view('admin.adminPages.uploadImage1');
	}
	
	}
?>