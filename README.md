# Dconstructor [![Build Status](https://travis-ci.org/jonathankowalski/omg.svg?branch=master)](https://travis-ci.org/jonathankowalski/dconstructor)

## Dependency Injection for Lazy People
### (all of us btw)

Everybody likes dependency injection (and if it is not your case, you should). However dependency injection sometimes leads us to write of useless code and that, everybody hates.

The purpose of Dconstructor is to free you from certain portions of code which do not serve in much, the happiness of developers as a matter of fact.

Indeed nowadays, we repeat many things, in the properties, in the signature of the constructor, in the body of the constructor. Yet to repeat it is null, time to dconstructor KISS

## Without DI

just a take a simple example

```php
class UserManager
{
    public function register($email){
        //some code
        $mailer = new Mailer();
        $mailer->send($email, "Hello !");
    }
}

class Mailer
{
    public function send($recipient, $message)
    {
        //some code
    }
}
```

## Classic DI

```php
class UserManager
{
    /**
     * @var Mailer
     */
    private $mailer;

    public function __construct(Mailer $mailer) {
        $this->mailer = $mailer;
    }

    public function register($email){
        //some code
        $this->mailer->send($email, "Hello !");
    }
}

class Mailer
{
    public function send($recipient, $message)
    {
        //some code
    }
}
```

## Dconstructor

```php
class UserManager
{
    /**
     * @var Mailer
     */
    private $mailer;

    public function register($email){
        //some code
        $this->mailer->send($email, "Hello !");
    }
}

class Mailer
{
    public function send($recipient, $message)
    {
        //some code
    }
}
```