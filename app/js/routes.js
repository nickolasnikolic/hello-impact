var blindApp = angular.module('blindApp', ['ui.router', 'ngSanitize', 'angular.filter'])
  .config(['$stateProvider',
    function($stateProvider) {
      $stateProvider

      .state('home', {
        url: '/',
        templateUrl: 'templates/home.html',
        controller: 'HomeController',
        onEnter: function($state) {
          console.log('home');
        }
      })

      .state('admin', {
        url: '/admin',
        templateUrl: 'templates/admin.html',
        controller: 'AdminController',
        onEnter: function($state) {
          console.log('admin');
        }
      })

      .state('shared', {
        url: '/shared/:userId/:listId',
        templateUrl: 'templates/shared.html',
        controller: 'SharedController',
        onEnter: function($state) {
          console.log('shared');
        }
      })

      .state('contact', {
        url: '/contact',
        templateUrl: 'templates/contact.html',
        controller: 'ContactController',
        onEnter: function($state) {
          console.log('contact');
        }
      })
    }
  ]);
