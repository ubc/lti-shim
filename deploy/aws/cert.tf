//resource "aws_acm_certificate" "cert" {
//  domain_name       = aws_alb.main.dns_name
//  validation_method = "DNS"
//
//  tags = {
//	Environment = "test"
//  }
//
//  lifecycle {
//	create_before_destroy = true
//  }
//}
