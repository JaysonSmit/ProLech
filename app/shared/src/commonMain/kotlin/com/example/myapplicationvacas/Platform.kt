package com.example.myapplicationvacas

interface Platform {
    val name: String
}

expect fun getPlatform(): Platform