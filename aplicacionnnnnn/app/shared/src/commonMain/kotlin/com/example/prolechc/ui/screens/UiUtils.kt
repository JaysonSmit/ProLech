package com.example.prolechc.ui.screens

// Quad de 4 valores — usado para mapear estado de acopio a colores/textos
internal data class Quad<A, B, C, D>(val a: A, val b: B, val c: C, val d: D)
internal operator fun <A, B, C, D> Quad<A, B, C, D>.component1() = a
internal operator fun <A, B, C, D> Quad<A, B, C, D>.component2() = b
internal operator fun <A, B, C, D> Quad<A, B, C, D>.component3() = c
internal operator fun <A, B, C, D> Quad<A, B, C, D>.component4() = d
