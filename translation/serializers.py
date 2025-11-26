# translation/serializers.py
from rest_framework import serializers

class TranslateRequestSerializer(serializers.Serializer):
    text = serializers.CharField()
    source = serializers.CharField(required=False, allow_blank=True)  # ví dụ: 'en'
    target = serializers.CharField()  # ví dụ: 'vi'

class TranslateResponseSerializer(serializers.Serializer):
    text = serializers.CharField()
    source = serializers.CharField()
    target = serializers.CharField()
    engine = serializers.CharField()
