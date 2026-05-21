package com.company.safedocshare.api

import com.company.safedocshare.models.ApiResponse
import com.company.safedocshare.models.LoginData
import com.company.safedocshare.models.LoginRequest
import com.company.safedocshare.models.PdfFile
import okhttp3.ResponseBody
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    @POST("api/login.php")
    suspend fun login(
        @Body request: LoginRequest
    ): Response<ApiResponse<LoginData>>

    @GET("api/pdfs.php")
    suspend fun getPdfs(
        @Header("Authorization") bearer: String
    ): Response<ApiResponse<List<PdfFile>>>

    @GET("api/pdf.php")
    @Streaming
    suspend fun downloadPdf(
        @Header("Authorization") bearer: String,
        @Query("id") pdfId: Int
    ): Response<ResponseBody>
}
