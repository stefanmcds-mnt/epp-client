<?php

namespace EppClient;

interface Interfaces
{
    public function Hello(...$Args);
    public function Login(...$Args);
    public function Logout(...$Args);
    public function Create(...$Args);
    public function Update(...$Args);
    public function Fetch(...$Args);
    public function Check(...$Args);
    public function Delete(...$Args);
    public function Transfer(...$Args);
}
