blindApp.factory('globals', function() {

    var userId = '';
    var userEmail = '';
    var items = [];
    var itemsService = {};

    itemsService.add = function(item) {
        items.push(item);
    };
    itemsService.list = function() {
        return items;
    };

    itemsService.getUserId = function(){
      return userId;
    };

    itemsService.setUserId = function( whatToSetItTo ){
      userId = whatToSetItTo;
    };

    itemsService.setUserEmail = function( whatToSetItTo ){
      userEmail = whatToSetItTo;
    };

    itemsService.getUserEmail = function(){
      return userEmail;
    };

    return itemsService;

});
