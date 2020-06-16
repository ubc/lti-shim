resource "aws_alb" "main" {
  name            = "shim-load-balancer"
  subnets         = aws_subnet.public.*.id
  security_groups = [aws_security_group.lb.id]
}

resource "aws_alb_target_group" "app" {
  name        = "shim-target-group"
  port        = var.app_port
  protocol    = "HTTP"
  vpc_id      = aws_vpc.main.id
  target_type = "ip"

  health_check {
    healthy_threshold   = "3"
    interval            = "30"
    protocol            = "HTTP"
    matcher             = "200"
    timeout             = "3"
    path                = var.health_check_path
    unhealthy_threshold = "2"
  }
}

//resource "aws_lb_listener" "front_end_http" {
//  load_balancer_arn = aws_alb.main.id
//  port              = "80"
//  protocol          = "HTTP"
//
//  default_action {
//	type = "redirect"
//
//	redirect {
//	  port        = "443"
//	  protocol    = "HTTPS"
//	  status_code = "HTTP_301"
//	}
//  }
//}

# Redirect all traffic from the ALB to the target group
resource "aws_alb_listener" "front_end" {
  load_balancer_arn = aws_alb.main.id
  port              = 80
  protocol          = "HTTP"
//  protocol          = "HTTPS"
//  ssl_policy        = "ELBSecurityPolicy-2016-08"
//  certificate_arn   = aws_acm_certificate.cert.arn

  default_action {
    target_group_arn = aws_alb_target_group.app.id
    type             = "forward"
  }

//  depends_on = [
//	aws_acm_certificate.cert
//  ]
}
