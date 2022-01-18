<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\Tweet;
use App\Entity\User;

class ApiController extends AbstractController
{

    function index()
    {
        $result = array();
        $result['users'] = $this->generateUrl(
            'api_get_users',
            array(),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $result['tweets'] = $this->generateUrl(
            'api_get_tweets',
            array(),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($result);
    }

    function getTweet($id)
    {
        // Obtenemos el tweet
        $entityManager = $this->getDoctrine()->getManager();
        $tweet = $entityManager->getRepository(Tweet::class)->find($id);

        // Si el tweet no existe devolvemos un error con código 404.
        if ($tweet == null) {
            return new JsonResponse([
                'error' => 'Tweet not found'
            ], 404);
        }

        // Creamos un objeto genérico y lo rellenamos con la información.
        $result = new \stdClass();
        $result->id = $tweet->getId();
        $result->date = $tweet->getDate();
        $result->text = $tweet->getText();

        // Para enlazar al usuario, añadimos el enlace API para consultar su información.
        $result->user = $this->generateUrl('api_get_user', [
            'id' => $tweet->getUser()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Para enlazar a los usuarios que han dado like al tweet, añadimos sus enlaces API.
        $result->likes = array();
        foreach ($tweet->getLikes() as $user) {
            $result->likes[] = $this->generateUrl('api_get_user', [
                'id' => $user->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        // Al utilizar JsonResponse, la conversión del objeto $result a JSON se hace de forma automática.
        return new JsonResponse($result);
    }

    function getTweetfonyUser($id)
    {
        // Obtenemos el usuario
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);

        // Si el usuario no existe devolvemos un error con código 404.
        if ($user == null) {
            return new JsonResponse([
                'error' => 'User not found'
            ], 404);
        }

        // Creamos un objeto genérico y lo rellenamos con la información.
        $result = new \stdClass();
        $result->id = $user->getId();
        $result->name = $user->getName();
        $result->username = $user->getUsername();

        // Para enlazar a los tweets que ha escrito, añadimos sus enlaces API.
        $result->tweets = array();
        foreach ($user->getTweets() as $tweet) {
            $result->tweets[] = $this->generateUrl('api_get_tweet', [
                'id' => $tweet->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        // Igualmente, añadimos el enlace API de los likes para consultar su información.
        $result->likes = array();
        foreach ($user->getLikes() as $tweet) {
            $result->likes[] = $this->generateUrl('api_get_tweet', [
                'id' => $tweet->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        // Devolvemos el resultado en formato JSON
        return new JsonResponse($result);
    }

    function getAllTweets()
    {
        // Obtenemos los tweets
        $entityManager = $this->getDoctrine()->getManager();
        $tweets = $entityManager->getRepository(Tweet::class)->findAll();

        // Si no hay tweets devolvemos un error con código 404.
        if ($tweets == null) {
            return new JsonResponse([
                'error' => 'Tweets not found'
            ], 404);
        }

        // Creamos un objeto para los resultados
        $results = new \stdClass();
        $results->count = count($tweets);
        $results->results = array();

        // Iteramos los resultado creando objetos con el id del tweet y su URL
        // Añadimos cada resultado al array de los resultados
        foreach ($tweets as $tweet) {
            $result = new \stdClass();
            $result->id = $tweet->getId();
            $result->url = $this->generateUrl('api_get_tweet', [
                'id' => $result->id,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            array_push($results->results, $result);
        }

        // Devolvemos el resultado en formato JSON
        return new JsonResponse($results);
    }

    function getAllTweetfonyUsers()
    {
        // Obtenemos los usuarios
        $entityManager = $this->getDoctrine()->getManager();
        $users = $entityManager->getRepository(User::class)->findAll();

        // Si no hay usuarios devolvemos un error con código 404.
        if ($users == null) {
            return new JsonResponse([
                'error' => 'Users not found'
            ], 404);
        }

        // Creamos un objeto para los resultados
        $results = new \stdClass();
        $results->count = count($users);
        $results->results = array();

        // Iteramos los resultado creando objetos con el nombre del usuario y su URL
        // Añadimos cada resultado al array de los resultados
        foreach ($users as $user) {
            $result = new \stdClass();
            $result->name = $user->getName();
            $result->url = $this->generateUrl('api_get_user', [
                'id' => $user->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            array_push($results->results, $result);
        }

        // Devolvemos el resultado en formato JSON
        return new JsonResponse($results);
    }

    function postTweet(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($request->request->get("userId"));
        if ($user == null) {
            return new JsonResponse([
                'error' => 'User (to post tweet) not found'
            ], 404);
        }

        $tweet = new Tweet();
        $tweet->setText($request->request->get("text"));
        $tweet->setDate(new \DateTime());
        $tweet->setUser($user);
        $entityManager->persist($tweet);
        $entityManager->flush();

        // Devolver la URL del resultado
        /*
        return new JsonResponse($this->generateUrl('api_get_tweet', [
            'id' => $tweet->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL), 201);
        */

        // Devolver el resultado en formato JSON
        $result = new \stdClass();
        $result->id = $tweet->getId();
        $result->date = $tweet->getDate();
        $result->text = $tweet->getText();
        $result->user = $this->generateUrl('api_get_user', [
            'id' => $user->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL); // Enlazamos con la URL del usuario
        $result->likes = array(); // Como no ha dado likes no hace falta crear enlaces
        return new JsonResponse($result, 201);
    }

    function postTweetfonyUser(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['userName' => $request->request->get("userName")]);
        if ($user) {
            return new JsonResponse([
                'error' => 'Username already exists'
            ], 409);
        }
        $user = new User();
        $user->setName($request->request->get("name"));
        $user->setUserName($request->request->get("userName"));
        $entityManager->persist($user);
        $entityManager->flush();

        // Devolver la URL del resultado
        /*
        return new JsonResponse($this->generateUrl('api_get_user', [
            'id' => $user->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL), 201);
        */

        // Devolver el resultado en formato JSON
        $result = new \stdClass();
        $result->id = $user->getId();
        $result->name = $user->getName();
        $result->userName = $user->getUserName();
        $result->likes = array(); // Como no tiene likes no hace falta crear enlaces
        $result->tweets = array(); // Como no tiene tweets no hace falta crear enlaces
        return new JsonResponse($result, 201);
    }
}
