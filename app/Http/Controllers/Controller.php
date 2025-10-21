<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="News Aggregator API Documentation",
 *     version="1.0.0",
 *     description="API for aggregating news from multiple sources with personalized feeds",
 *     @OA\Contact(
 *         email="support@newsaggregator.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://news-aggregator.test",
 *     description="Local Development Server"
 * )
 * 
 * @OA\Server(
 *     url="http://news-aggregator.com",
 *     description="Production Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication endpoints (login, logout, user info)"
 * )
 * 
 * @OA\Tag(
 *     name="Articles",
 *     description="Public articles endpoints with filtering and search"
 * )
 * 
 * @OA\Tag(
 *     name="User Feed",
 *     description="Personalized feed based on user preferences"
 * )
 */
abstract class Controller
{
    //
}
