# ALB Security Group: Edit to restrict access to the application
resource "aws_security_group" "lb" {
  name        = "shim-load-balancer-security-group"
  description = "controls access to the ALB"
  vpc_id      = aws_vpc.main.id

  ingress {
    protocol    = "tcp"
    from_port   = var.app_port
    to_port     = var.app_port
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    protocol    = "-1"
    from_port   = 0
    to_port     = 0
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# Traffic to the ECS cluster should only come from the ALB
resource "aws_security_group" "ecs_tasks" {
  name        = "shim-ecs-tasks-security-group"
  description = "allow inbound access from the ALB only"
  vpc_id      = aws_vpc.main.id

  ingress {
    protocol        = "tcp"
    from_port       = var.app_port
    to_port         = var.app_port
    security_groups = [aws_security_group.lb.id]
  }

  egress {
    protocol    = "-1"
    from_port   = 0
    to_port     = 0
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_security_group_rule" "db_ingress_sgr" {
  type                     = "ingress"
  security_group_id        = aws_security_group.ecs_rds.id
  from_port                = aws_db_instance.default.port
  to_port                  = aws_db_instance.default.port
  protocol                 = "tcp"
  cidr_blocks = aws_subnet.private.*.cidr_block
}

resource "aws_security_group" "ecs_rds" {
  name = "shim-ecs-rds-security-group"
  description = "allow traffic between ecs and rds"
  vpc_id = aws_vpc.main.id

//  egress {
//	protocol    = "-1"
//	from_port   = 0
//	to_port     = 0
//	cidr_blocks = ["0.0.0.0/0"]
//  }
}
