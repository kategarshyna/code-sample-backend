<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class JwtTokenAuthenticator extends AbstractGuardAuthenticator
{
    private JWTTokenManagerInterface $jwtTokenManager;
    private TokenExtractorInterface $tokenExtractor;

    public function __construct(JWTTokenManagerInterface $jwtManager, TokenExtractorInterface $tokenExtractor)
    {
        $this->jwtTokenManager = $jwtManager;
        $this->tokenExtractor = $tokenExtractor;
    }

    public function supports(Request $request): bool
    {
        return $this->getToken($request) !== false;
    }

    protected function getTokenExtractor(): TokenExtractorInterface
    {
        return $this->tokenExtractor;
    }

    public function getCredentials(Request $request): ?PreAuthenticationJWTUserToken
    {
        $tokenExtractor = $this->getTokenExtractor();

        if (false === ($jsonWebToken = $tokenExtractor->extract($request))) {
            return null;
        }

        $preAuthToken = new PreAuthenticationJWTUserToken($jsonWebToken);

        try {
            if (!$payload = $this->jwtTokenManager->decode($preAuthToken)) {
                throw new InvalidTokenException('Invalid JWT Token');
            }

            $preAuthToken->setPayload($payload);
        } catch (JWTDecodeFailureException $e) {
            if (JWTDecodeFailureException::EXPIRED_TOKEN === $e->getReason()) {
                $expiredTokenException = new ExpiredTokenException();
                $expiredTokenException->setToken($preAuthToken);
                throw $expiredTokenException;
            }

            throw new InvalidTokenException('Invalid JWT Token', 0, $e);
        }

        return $preAuthToken;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        try {
            if (!$credentials instanceof PreAuthenticationJWTUserToken) {
                throw new \Exception('Invalid credentials');
            }

            return $userProvider->loadUserByUsername($credentials->getPayload()['username']);
        } catch (\Exception $exception) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        // No further checks are needed, assuming the token is valid
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(['message' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // Authentication success, nothing additional needed here
    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(['message' => 'Authentication Required'], Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function getToken(Request $request)
    {
        $extractor = new AuthorizationHeaderTokenExtractor(
            'Bearer',
            'Authorization'
        );

        return $extractor->extract($request);
    }
}