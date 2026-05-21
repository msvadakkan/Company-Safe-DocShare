package com.company.safedocshare.models

import com.google.gson.annotations.SerializedName

data class LoginRequest(
    val username: String,
    val password: String
)

data class LoginData(
    val token: String,
    val username: String,
    @SerializedName("expires_at") val expiresAt: String
)

data class PdfFile(
    val id: Int,
    val name: String,
    val size: Long,
    @SerializedName("uploaded_at") val uploadedAt: String
) {
    fun formattedSize(): String {
        return when {
            size >= 1_048_576 -> "%.1f MB".format(size / 1_048_576.0)
            size >= 1_024     -> "%.1f KB".format(size / 1_024.0)
            else              -> "$size B"
        }
    }
}

data class ApiResponse<T>(
    val success: Boolean,
    val message: String,
    val data: T?
)
