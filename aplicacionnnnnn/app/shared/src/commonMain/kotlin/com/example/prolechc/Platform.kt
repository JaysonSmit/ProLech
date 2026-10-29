package com.example.prolechc

interface Platform {
    val name: String
}

expect fun getPlatform(): Platform