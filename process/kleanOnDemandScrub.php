<?php 

if($op == "ajaxcall") {
	if(isset($_POST['action'])) {
		if($_POST['action'] == 'getTagList') {
			$userId 	= $_POST['userId'];
			$orderBy 	= "Id";
			$ascending 	= true;
			$page 		= 0;
			$table 		= "ContactGroup";
			$queryData 	= array('Id' => '%'); 

			$listArr = [];
			$category = $infusionsoft->data()->query('ContactGroupCategory', 1000, 0, $queryData, ["Id", "CategoryName"], $orderBy, $ascending);
			$tags = $infusionsoft->data()->query($table, 1000, $page, $queryData, ["Id", "GroupName", "GroupDescription", "GroupCategoryId"], $orderBy, $ascending);

			for($x = 0; $x < count($category); $x++) {
				for($y = 0; $y < count($tags); $y++) {

					if($tags[$y]['GroupCategoryId'] == $category[$x]['Id']) {
						$listItem = new StdClass();
						$listItem->GroupCategoryId 	= $tags[$y]['GroupCategoryId'];
						$listItem->GroupName 		= $tags[$y]['GroupName'];
						$listItem->Id 				= $tags[$y]['Id'];

						if(count($category)) {
							$listItem->CategoryName = $category[$x]['CategoryName'];
						}else {
							$listItem->CategoryName = 'Null';
						}

						array_push($listArr, $listItem);
					}	
				}
			}

			$data = array(
				'category' => $category,
				'tags' => $tags,
				'result' => $listArr,
				// 'result' => $listArr,
				'success' => 2
			);

			echo json_encode($data);

			die();
		}
	}
}