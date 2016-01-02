blindApp.controller('IndexController', ['$scope', '$state', 'globals', function($scope, $state, globals) {
  SocialShareKit.init({selector: '.sharing-list'});//sharekit
}])

blindApp.controller('HomeController', ['$scope', '$state', 'globals', function($scope, $state, globals) {

  //collect user/pass
  //if login is super secret
  //move to admin
  $scope.login = function(){

    var username = $('#username').val();
    var password = $('#password').val();

    $.post('../api/home',{ 'username': username, 'password': password })
      .success(function(data){

        if(data != 0){
          var holdingCell = JSON.parse(data);
          globals.setUserId( holdingCell[0].userid );
          globals.setUserEmail( username );

          $state.go('admin');
        }
      })
      .error(function(error){
        console.log('error is: ');
        console.log(error);
      });

  };

  //collect user/pass
  //if login is super secret
  //move to admin
  $scope.register = function(){

    var username = $('#registrationEmail').val();
    var password = $('#registrationPassword').val();
    var nickname = $('#registrationNickname').val();

    $.post('../api/home/register/',{ 'email': username, 'password': password, 'nickname': nickname })
      .success(function(data){
        if(data != 0){
          var holdingCell = JSON.parse(data);
          globals.setUserId( holdingCell[0].userid );
          globals.setUserEmail( username );
        }
      })
      .error(function(error){
        console.log('error is: ');
        console.log(error);
      });

  };

  $scope.passwordReminder = function(){
    var email = $('#passwordEmail').val();
    $('#passwordEmail').val('');

    $.post('../api/password',{ 'email': email })
        .error(function(error){
          console.log('error is: ');
          console.log(error);
        });

  };

  //go get the lists
  $.get('../api/home' )
      .success(function(data){
        $scope.lists = JSON.parse(data);
        $scope.$apply();
        SocialShareKit.init();//sharekit
      });

}])

blindApp.controller('AdminController', ['$scope', '$state', 'globals', function($scope, $state, globals) {

  //if the username isn't set, return home for login
  if( globals.getUserId() == '' ){
    $state.go('home');
  }

  $scope.classes = [];

  $.get('../api/admin/' + globals.getUserId() )
    .success(function(data){
      $scope.classes = JSON.parse(data);
      $scope.$apply();
    });

  //simple logout
  $scope.logout = function(){
    globals.setUserId(null);
    $state.go('home');
  };

  //on add class
  //add class
  $scope.addClass = function(){
    var classTitle = $('#classTitle').val();
    var classNumber = $('#classNumber').val();
    var admin = globals.getUserId();

    $('#classTitle').val('');
    $('#classNumber').val('');

    $.post('../api/admin/add/class/',{ 'title': classTitle, 'number': classNumber, 'admin': admin })
        .success(function(data){
          $scope.classes = JSON.parse(data);
          $scope.$apply();
        })
        .error(function(error){ console.log(error) });
  };

  //on remove class
  //remove class
  $scope.removeClass = function(index, classid){
    $scope.classes.splice( index, 1 );
    $.post('../api/admin/remove/class/',{ 'id': classid });
  };

  //prep for book nested array
  var source = 0;
  var classIndex = 0;
  $scope.frustrating = function(setTheBastard, classId){
    source = setTheBastard;
    classIndex = classId;
  };

  $scope.amazonLookup = function(){
    var isbn = $('#bookIsbn').val();
    if( isbn.length > 8 ){
      $.get( '../api/amazon/small/lookup/' + isbn )
          .success(function(data){
            data = JSON.parse(data);
            $scope.book = data.Items.Item.ItemAttributes;
            $scope.book.ISBN = isbn;
            $scope.$apply();
          })
          .error(function(error){ console.log(error); });
    }
  };

  $scope.clearText = function(){ //clear the text field on focus
    $('#bookIsbn').val(''); //todo switch to current fields
  };

  //on add book
  //add book
  $scope.addBook = function(){

    var admin = globals.getUserId();
    var book = $scope.book;



    //add to db
    $.post('../api/admin/add/book/',{
      title: book.title,
      address1: book.address1,
      address2: book.address2,
      address3: book.address3,

      city: book.city,
      state: book.state,
      zipcode: book.zipcode,

      country: book.country,

      phone: book.phone,
      fax: book.fax,
      description: book.description,

      hours_monday: book.hours_monday,
      hours_tuesday: book.hours_tuesday,
      hours_wednesday: book.hours_wednesday,
      hours_thursday: book.hours_thursday,
      hours_friday: book.hours_friday,
      hours_saturday: book.hours_saturday,
      hours_sunday: book.hours_sunday,

      class: classIndex, 'admin': admin
    })
        .success(function(data){
          $scope.classes = JSON.parse(data); //HACK refreshing whole list when single book is added
          $scope.$apply();
        })
        .error(function(error){ console.log(error) });
  };

  //get book for edit
  //add book
  $scope.editBookLink = function(){

    var admin = globals.getUserId();
    var book = $scope.classes[which].items[index];

    console.log(admin,book);

    $('#myAddBookModal').modal('show');

    $scope.book = book;

  };

  //on remove book
  //remove book
  $scope.removeBook = function(index, which){

    var book = $scope.classes[which].items[index];

    $scope.classes[which].items.splice( index, 1 );

    //remove from db
    $.post('../api/admin/remove/book/',{ 'bookid': book.id })
        .error(function(error){console.log(error)});
  };

  $scope.qrcodeurl = '';
  //on generate
  //move to display with cart qr code
  $scope.generateQrCode = function( whichClass ){
    $('#qrcode').empty();

    $scope.qrcodeurl = 'https://hello-impact.herokuapp.com'; //site root...
    $scope.qrcodeurl += '/app/#/shared/';

    var thisClass = $scope.classes[whichClass];

    $scope.qrcodeurl +=  + globals.getUserId() + '/' + thisClass.id;
    new QRCode( document.getElementById("qrcode"), $scope.qrcodeurl );
  };

}])

blindApp.controller('SharedController', ['$scope', '$state', '$stateParams', function($scope, $state, $stateParams) {

  var userId = $stateParams.userId;
  var listId = $stateParams.listId;
  //go get the lists
  $.get('../api/shared/' + userId + '/' + listId )
      .success(function(data){
        $scope.list = JSON.parse(data);
        $scope.$apply();
        SocialShareKit.init();//sharekit
      });

}])

blindApp.controller('ContactController', ['$scope', '$state', function($scope, $state) {}])
