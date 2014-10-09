'use strict';

/**
 * @ngdoc overview
 * @name aiselApp
 *
 * @description
 * Router for Homepage
 */

define(['app'], function (app) {
    console.log('Homepage Router Loaded ...');
    app.config(function ($provide, $routeProvider) {
        $routeProvider
            // Homepage
            .when('/:locale/', {
                templateUrl: 'app/Aisel/homepage/views/homepage.html',
                controller: 'HomepageCtrl'
            })
    });
});