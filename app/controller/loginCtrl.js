angular.module('SRIGPortal', ['ngAnimate'])
	.controller('LoginCtrl', function( $scope , $http) {
		
		$scope.error = false;
		$scope.timeout = false;


		$http.get('http://192.168.77.14/index.php').then(function(response){
				console.log(response)
			});

		// check for timeout hash.  if so then show the timeout message
		var hash = window.location.hash;
		if(hash == '#timeout'){
			$scope.timeout = true;
			$scope.timeout_message = 'For security purposes your session has expired.  Please login again.';
		}

		// calls login endpoint
		$scope.login = function(){
			$scope.error = false; //reset everytime for UI/UX purposes

			// TODO rethink logic for form handling.  could probably use required property to make less code
			if($scope.user == undefined || $scope.pass == undefined){
				$scope.user_error = $scope.user == undefined ? true : false;
				$scope.pass_error = $scope.pass == undefined ? true : false;
				return false;
			}

			if($scope.user.length > 0 && $scope.pass.length > 0)
			{
				// clear values
				$scope.user_error = false;
				$scope.pass_error = false;

				// call endpoint with data payload
				$http.post('/api/login', { email: $scope.user, pass: $scope.pass }).then(function(response){
					var didSucceed = response.data.success;
					
					if(didSucceed){
						// page to go to upon sucessful login
						window.location = 'accountManager.html';
					} else {

						// call was a sucess, but the intended action was not able to process
						switch (response.data.error_code) {
							case 600:
								$scope.error = true;
								$scope.error_message = 'Please enter a valid password';
								break;
							case 601:
								$scope.error = true;
								$scope.error_message = 'Please enter a valid username';
								break;
						}

					}

				},function(data) {
	        		// call failed epically
					$scope.error = true;
					$scope.error_message = 'The server could not process your request, please contact your administrator!';
			    });
			}

		}
	});
