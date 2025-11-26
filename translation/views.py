from django.shortcuts import render

# Create your views here.
# translation/views.py
from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status
from .serializers import TranslateRequestSerializer, TranslateResponseSerializer

# Dùng googletrans
from googletrans import Translator

translator = Translator()

class TranslateView(APIView):
    def post(self, request):
        serializer = TranslateRequestSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        data = serializer.validated_data

        text = data['text']
        target = data['target']
        source = data.get('source') or 'auto'

        try:
            result = translator.translate(text, src=source, dest=target)
            resp = TranslateResponseSerializer({
                'text': result.text,
                'source': result.src,
                'target': target,
                'engine': 'googletrans',
            })
            return Response(resp.data, status=status.HTTP_200_OK)
        except Exception as e:
            return Response(
                {'error': 'Translation failed', 'detail': str(e)},
                status=status.HTTP_502_BAD_GATEWAY
            )
